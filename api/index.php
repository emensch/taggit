<?php

require '../Slim/Slim.php';
require 'password.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() use ($app) {
            $result = array();
            $sql = "SELECT * FROM Users ORDER BY name";
            $sql2 = "SELECT Tags.name, Tags.id, Subscriptions.onTop FROM Subscriptions, Tags 
                    WHERE Tags.ID = Subscriptions.tagID 
                    AND Subscriptions.userID = :userID";
            try { 
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $query = $db->query($sql);
                foreach($query as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":userID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['id'], "top" => $row2['onTop']);
                    }
                    $result[] = array(
                        "username" => $row['name'],
                        "email" => $row['email'],
                        "score" => $row['score'],
                        "tags" => $tags
                    );    
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);    
});

// v1 group 
$app->group('/v1', function() use ($app) {
    // Manage logins
    $app->post('/login', function() use ($app) {
        $json = $app->request->getBody();
        $data = json_decode($json, true);
        $response = array("userID" => "", "apiKey" => "");

        $validate = "SELECT COUNT(*) as valid, id, passwordHash
                FROM Users
                WHERE email = :email";
        $apikey = "INSERT APIKeys (userID, apiKey, dateTime)  
                VALUES (:userID, :key, :dateTime) 
                ON DUPLICATE KEY UPDATE apiKey = :key, dateTime = :dateTime";

        try {
            // Check credentials
            $db = getConnection();
            $stmt = $db->prepare($validate);
            $stmt->bindParam(":email", $data['email']);
            $stmt->execute();
            $row = $stmt->fetch();

            $pwValid = password_verify($data['passwordHash'], $row['passwordHash']);
            if($row['valid'] >= 1 && $pwValid) {
                // Add new API key to table
                $value = strtotime(getTime()).$data['name'];
                $key = hash("sha256", $value);
                $stmt = $db->prepare($apikey);
                $stmt->bindParam(":userID", $row['id']);
                $stmt->bindParam(":key", $key);
                $stmt->bindParam(":dateTime", getTime());
                $stmt->execute();

                $response['userID'] = $row['id'];
                $response['apiKey'] = $key; 
                echo json_encode($response);
            } else {
                $app->response->setStatus(401);
            }

        } catch(Exception $e) {
            $app->response->setStatus(500);
            echo $e;
        }

    })->name('Add post');

    $app->post('/logout', 'authenticateKey', function() use ($app) {
        $userID = $app->request->headers->get("Php-Auth-User"); 

        $sql = "DELETE FROM APIKeys WHERE userID = :ID";

        try {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":ID", $userID);
            $stmt->execute();
        } catch(Exception $e) {
            $app->response->setStatus(500);
            echo $e;
        }
    });

    // Users group
    $app->group('/users', function() use ($app) {
        // Get all users
        $app->get('/', function () {
            $result = array();
            $sql = "SELECT ID, name, score FROM Users ORDER BY name";
            $sql2 = "SELECT Tags.name, Tags.id, Subscriptions.onTop FROM Subscriptions, Tags 
                    WHERE Tags.ID = Subscriptions.tagID 
                    AND Subscriptions.userID = :userID";
            try { 
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $query = $db->query($sql);
                foreach($query as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":userID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['id'], "top" => $row2['onTop']);
                    }
                    $result[] = array(
                        "username" => $row['name'],
                        "score" => $row['score'],
                        "tags" => $tags
                    );    
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);
        });
        
        // Get user with ID
        $app->get('/:id', 'authenticateKey', function ($id) {
            $result = array();
            $sql = "SELECT ID, name, score FROM Users WHERE ID = :ID";
            $sql2 = "SELECT Tags.name, Tags.id, Subscriptions.onTop FROM Subscriptions, Tags 
                    WHERE Tags.ID = Subscriptions.tagID 
                    AND Subscriptions.userID = :userID";
            try { 
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch();

                $stmt2 = $db->prepare($sql2);
                $stmt2->bindParam(":userID", $id, PDO::PARAM_INT);
                $stmt2->execute();
                $tags = array();
                foreach($stmt2 as $row2) {
                    $tags[] = array("name" => $row2['name'], "id" => $row2['id'], "top" => $row2['onTop']);
                }
                $result[] = array(
                    "username" => $row['name'],
                    "score" => $row['score'],
                    "tags" => $tags
                );  
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);
        });

        // Get user with ID's posts
        $app->get('/:id/posts', 'authenticateKey', function ($id) use ($app) {
            $result = array();
            $userID = $app->request->headers->get("Php-Auth-User"); 

            $sql = "SELECT Posts.*, COALESCE(pv.value, 0) as voteValue, Users.name FROM Posts
            JOIN Users ON Posts.authorID = Users.ID
            LEFT JOIN (SELECT * FROM PostVotes WHERE PostVotes.userID = :userID) as pv 
            ON Posts.id = pv.postID
            WHERE Posts.authorID = :ID";

            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":postID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['ID']);       
                    }
                    $timeInterval = getTimeInterval($row['dateTime']);
                    $result[] = array(
                        "ID" => $row['ID'],
                        "authorName" => $row['name'],
                        "authorID" => $row['authorID'],
                        "votes" => $row['votes'],
                        "voteValue" => $row['voteValue'],
                        "title" => $row['title'],
                        "link" => $row['link'],
                        "body" => $row['body'],
                        "numComments" => $row['numComments'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval,             
                        "tags" => $tags
                    );
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);
        });

        // Get user with ID's frontpage posts
        $app->get('/:id/frontpage', 'authenticateKey', function ($id) use ($app) {
            $result = array();
            $userID = $app->request->headers->get("Php-Auth-User"); 

            $sql = "SELECT DISTINCT Posts.*, COALESCE(pv.value, 0) as voteValue, Users.name FROM Posts
                    JOIN PostTags ON Posts.id = PostTags.postID
                    JOIN Subscriptions ON PostTags.tagID = Subscriptions.tagID
                    JOIN Users ON Posts.authorID = Users.ID
                    LEFT JOIN (SELECT * FROM PostVotes WHERE PostVotes.userID = :userID) as pv 
                    ON Posts.id = pv.postID 
                    WHERE Subscriptions.userID = :ID
                    ORDER BY Posts.Votes DESC, DateTime DESC";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":postID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    $timeInterval = getTimeInterval($row['dateTime']); 
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['ID']);       
                    }      
                    $result[] = array(
                        "ID" => $row['ID'],
                        "authorName" => $row['name'],
                        "authorID" => $row['authorID'],
                        "votes" => $row['votes'],
                        "voteValue" => $row['voteValue'],
                        "title" => $row['title'],
                        "link" => $row['link'],
                        "body" => $row['body'],
                        "numComments" => $row['numComments'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval,             
                        "tags" => $tags
                    );
                }    

            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);            
        });

        // Get user with ID's comments
        $app->get('/:id/comments', 'authenticateKey', function ($id) {
            $result = array();
            $sql = "SELECT Comments.*, Users.name FROM Comments, Users 
                    WHERE Comments.authorID = :ID AND Users.ID = Comments.authorID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                $timeInterval = getTimeInterval($row['dateTime']);
                foreach($stmt as $row) {
                    $result[] = array(
                        "ID" => $row['id'],
                        "parentID" => $row['postID'],
                        "authorID" => $row['authorID'],
                        "authorName" => $row['name'],
                        "body" => $row['body'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval
                    );
                }

            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);
        });

        // Add a user
        $app->post('/', function() use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $response = array("usernameError" => 0, "emailError" => 0, "emailFormatError" => 0);
            $entryOK = True;

            
            $usernameQ = "SELECT COUNT(*) as nameExists FROM Users WHERE name = :username";
            $emailQ = "SELECT COUNT(*) as emailExists FROM Users WHERE email = :email";
            $sql = "INSERT INTO Users (name, passwordHash, email, score)
                    VALUES (:name, :passwordHash, :email, 0)";

            try {
                // Check name uniqueness
                $db = getConnection();
                $stmt = $db->prepare($usernameQ);
                $stmt->bindParam(":username", $data['name']);
                $stmt->execute();
                $row = $stmt->fetch();
                if($row['nameExists'] >= 1) {
                    $app->response->setStatus(409);
                    $response['usernameError'] = 1;
                    $entryOK = False;
                }
                // Check email format
                if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $app->response->setStatus(409);
                    $response['emailFormatError'] = 1;
                    $entryOK = False;
                } else {
                    // Check email uniqueness
                    $stmt = $db->prepare($emailQ);
                    $stmt->bindParam(":email", $data['email']);
                    $stmt->execute();
                    $row = $stmt->fetch();
                    if($row['emailExists'] >= 1) {
                        $app->response->setStatus(409);
                        $response['emailError'] = 1;
                        $entryOK = False;
                    }
                }

                // Add user
                if($entryOK == True) {
                    $hash = password_hash($data['passwordHash'], PASSWORD_DEFAULT);

                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":name", $data['name']);
                    $stmt->bindParam(":passwordHash", $hash);
                    $stmt->bindParam(":email", $data['email']);
                    $stmt->execute();
                    $app->response->setStatus(201);
                } else {
                    echo json_encode($response);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }

        });
        
        // Update a user
        $app->put('/:id', 'authenticateKey', function($id) use ($app) {

        });
    });

    // Posts group
    $app->group('/posts', function() use ($app) {
        // Get all posts
        $app->get('/', 'authenticateKey', function() use ($app) {
            $result = array();
            $userID = $app->request->headers->get("Php-Auth-User");  

            $sql = "SELECT Posts.*, COALESCE(pv.value, 0) as voteValue, Users.name FROM Posts
                    JOIN Users ON Users.ID = Posts.authorID
                    LEFT JOIN (SELECT * FROM PostVotes WHERE PostVotes.userID = :userID) as pv
                    ON Posts.ID = pv.postID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try { 
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":postID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['ID']);       
                    }
                    $timeInterval = getTimeInterval($row['dateTime']);
                    $result[] = array(
                        "ID" => $row['ID'],
                        "authorName" => $row['name'],
                        "authorID" => $row['authorID'],
                        "votes" => $row['votes'],
                        "voteValue" => $row['voteValue'],
                        "title" => $row['title'],
                        "link" => $row['link'],
                        "body" => $row['body'],
                        "numComments" => $row['numComments'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval,             
                        "tags" => $tags
                    );
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);         
        });

        // Get post with ID
        $app->get('/:id', 'authenticateKey', function ($id) use ($app) {
            $result = array();
            $userID = $app->request->headers->get("Php-Auth-User");  

            $sql = "SELECT Posts.*, COALESCE(pv.value, 0) as voteValue, Users.name FROM Posts
                    JOIN Users ON Users.ID = Posts.authorID
                    LEFT JOIN (SELECT * FROM PostVotes WHERE PostVotes.userID = :userID) as pv
                    ON Posts.ID = pv.postID WHERE Posts.ID = :ID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch();

                $stmt2 = $db->prepare($sql2);
                $stmt2->bindParam(":postID", $row['ID'], PDO::PARAM_INT);
                $stmt2->execute();
                $tags = array();
                foreach($stmt2 as $row2) {
                    $tags[] = array("name" => $row2['name'], "id" => $row2['ID']);       
                }
                $timeInterval = getTimeInterval($row['dateTime']);
                $result[] = array(
                    "ID" => $row['ID'],
                    "authorName" => $row['name'],
                    "authorID" => $row['authorID'],
                    "votes" => $row['votes'],
                    "voteValue" => $row['voteValue'],
                    "title" => $row['title'],
                    "link" => $row['link'],
                    "body" => $row['body'],
                    "numComments" => $row['numComments'],
                    "editedOn" => $row['editedOn'],
                    "dateTime" => $timeInterval,             
                    "tags" => $tags
                );
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);              
        });

        // Get post with ID's comments
        $app->get('/:id/comments', 'authenticateKey', function ($id) {
            $result = array();
            $sql = "SELECT Comments.*, Users.name FROM Comments, Users 
                    WHERE Comments.postID = :ID AND Users.ID = Comments.authorID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $timeInterval = getTimeInterval($row['dateTime']);
                    $result[] = array(
                        "ID" => $row['id'],
                        "parentID" => $row['postID'],
                        "authorID" => $row['authorID'],
                        "authorName" => $row['name'],
                        "body" => $row['body'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval
                    );
                }

            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);          
        });

        // Add a comment to post with ID
        $app->post('/:id/comments', 'authenticateKey', function($id) use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true); 
            $userID = $app->request->headers->get("Php-Auth-User");  

            $parentexists = "SELECT COUNT(*) AS postExists FROM Posts WHERE ID = :postID";
            $sql = "INSERT INTO Comments (postID, authorID, body, dateTime)
                    VALUES (:postID, :authorID, :body, :dateTime);
                    UPDATE Posts SET numComments=numComments+1 WHERE ID = :postID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($parentexists);
                $stmt->bindParam(":postID", $id);
                $stmt->execute();
                $row = $stmt->fetch();

                if($row['postExists'] >= 1) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":postID", $id, PDO::PARAM_INT);
                    $stmt->bindParam(":authorID", $userID, PDO::PARAM_INT);
                    $stmt->bindParam(":body", $data['body']);
                    $stmt->bindParam(":dateTime", getTime());
                    $stmt->execute();
                    $app->response->setStatus(201);
                } else {
                    $app->response->setStatus(404);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        }); 

        // Add a post
        $app->post('/', 'authenticateKey', function() use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $userID = $app->request->headers->get("Php-Auth-User");    

            $sql = "INSERT INTO Posts (authorID, title, link, body, dateTime, votes, numComments) 
                    VALUES (:authorID, :title, :link, :body, :dateTime, 0, 0)";
            $sql2 = "INSERT IGNORE INTO Tags (name, userCount)
                    VALUES (:tagName, 0);
                    INSERT INTO PostTags (tagID, postID)
                    SELECT Tags.id, :postID FROM Tags
                    WHERE Tags.name = :tagName";
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":authorID", $userID, PDO::PARAM_INT);
                $stmt->bindParam(":title", $data['title']);
                $stmt->bindParam(":link", $data['link']);
                $stmt->bindParam(":body", $data['body']);
                $stmt->bindParam(":dateTime", getTime());
                $stmt->execute();
                $foreignKey = $db->lastInsertID();

                foreach($data['tags'] as $tag) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":postID", $foreignKey);
                    $stmt2->bindParam(":tagName", strtolower($tag));  
                    $stmt2->execute();  
                }
                $app->response->setStatus(201);
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            } 
            
        });

        // Update post with ID
        $app->put('/:id', 'authenticateKey', function($id) use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $userID = $app->request->headers->get("Php-Auth-User");  

            $owner = "SELECT authorID FROM Posts Where id = :id";
            $sql = "UPDATE Posts
                    SET title = :title, body = :body, editedOn = :editedOn
                    WHERE id = :id";
            try {
                $db = getConnection();
                $stmt = $db->prepare($owner);
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $row = $stmt->fetch();
                if($row['authorID'] == $userID) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":title", $data['title']);
                    $stmt->bindParam(":body", $data['body']);
                    $stmt->bindParam(":editedOn", getTime());
                    $stmt->bindParam(":id", $id);
                    $stmt->execute();
                } else {
                    $app->response->setStatus(401);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });

        // Update/add vote
        $app->put('/:id/vote', 'authenticateKey', function($id) use ($app) {
            $userID = $app->request->headers->get("Php-Auth-User"); 
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $postexists = "SELECT COUNT(*) AS postExists FROM Posts WHERE ID = :postID";
            $hasVotedQ = "SELECT COUNT(*) as voted, value FROM PostVotes WHERE userID = :userID AND postID = :postID";
            $updateVoteQ = "UPDATE PostVotes SET value = :value WHERE userID = :userID AND postID = :postID;
                            UPDATE Posts SET votes = votes + :diff WHERE ID = :postID";
            $insertVoteQ = "INSERT INTO PostVotes (postID, userID, value)
                            VALUES (:postID, :userID, :value);
                            UPDATE Posts SET votes = votes + :diff WHERE ID = :postID";

            try {
                // Check if user has already voted, store vote value (for correct incr/decr calc)
                $db = getConnection();
                $stmt = $db->prepare($postexists);
                $stmt->bindParam(":postID", $id, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch();

                // if post exists
                if($row['postExists'] >= 1) {
                    $stmt = $db->prepare($hasVotedQ);
                    $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                    $stmt->bindParam(":postID", $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $row = $stmt->fetch();

                    // If vote exists
                    if($row['voted'] >= 1) {
                        if($data['value'] != 0) {
                            $voteDiff = $data['value'] - $row['value'];
                        } else {
                            $voteDiff = -$row['value'];
                        }

                        // Run commit only if vote needs to be modified 
                        if($voteDiff) {
                            $stmt = $db->prepare($updateVoteQ);
                            $stmt->bindParam(":value", $data['value'], PDO::PARAM_INT);
                            $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                            $stmt->bindParam(":postID", $id, PDO::PARAM_INT);
                            $stmt->bindParam(":diff", $voteDiff, PDO::PARAM_INT);
                            $stmt->execute();
                        }
                    } else {
                        $stmt = $db->prepare($insertVoteQ);
                        $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                        $stmt->bindParam(":postID", $id, PDO::PARAM_INT);
                        $stmt->bindParam(":value", $data['value'], PDO::PARAM_INT);
                        $stmt->bindParam(":diff", $data['value']);
                        $stmt->execute();
                    }  
                } else {
                    $app->response->setStatus(404);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            
        });

        // Delete post with ID
        $app->delete('/:id', 'authenticateKey', function($id) use ($app) {
            $userID = $app->request->headers->get("Php-Auth-User");  

            $owner = "SELECT authorID FROM Posts Where id = :id";
            $sql = "DELETE FROM Posts WHERE id = :id; 
                    DELETE FROM Comments WHERE postID = :id;
                    DELETE FROM PostTags WHERE postID = :id;
                    DELETE FROM PostVotes WHERE postID = :id";

            try {
                $db = getConnection();
                $stmt = $db->prepare($owner);
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $row = $stmt->fetch();
                // Post owner verify
                if($row['authorID'] == $userID) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":id", $id);
                    $stmt->execute();
                } else {
                    $app->response->setStatus(401);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });
    });

    // Comments group
    $app->group('/comments', function() use ($app) {
        // Update comment with ID
        $app->put('/:id', 'authenticateKey', function($id) use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $userID = $app->request->headers->get("Php-Auth-User");  

            $owner = "SELECT authorID FROM Comments Where id = :id";
            $sql = "UPDATE Comments
                    SET body = :body, editedOn = :editedOn
                    WHERE id = :id";
            try {
                $db = getConnection();
                $stmt = $db->prepare($owner);
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $row = $stmt->fetch();
                if($row['authorID'] == $userID) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":body", $data['body']);
                    $stmt->bindParam(":editedOn", getTime());
                    $stmt->bindParam(":id", $id);
                    $stmt->execute();
                } else {
                    $app->response->setStatus(401);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        }); 

        // Delete comment with ID
        $app->delete('/:id', 'authenticateKey', function($id) use ($app) {
            $userID = $app->request->headers->get("Php-Auth-User");

            $owner = "SELECT authorID, postID FROM Comments Where id = :id";
            $sql = "DELETE FROM Comments WHERE id = :id;
                    UPDATE Posts SET numComments=numComments-1 WHERE id = :parentID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($owner);
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $row = $stmt->fetch();
                if($row['authorID'] == $userID) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":id", $id);
                    $stmt->bindParam(":parentID", $row['postID']);
                    $stmt->execute();
                } else {
                    $app->response->setStatus(401);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });
    });

    // Tags group
    $app->group('/tags', function() use ($app) {
        // Get all tags
        $app->get('/', 'authenticateKey', function() {
            $result = array();
            $sql = "SELECT Tags.* FROM Tags";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->execute();
                foreach($stmt as $row) {
                    $result[] = array(
                        "tagID" => $row['ID'],
                        "tagName" => $row['name'],
                        "subscribers" => $row['usercount']
                    );
                }
                echo json_encode($result);
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }   
        });

        // Get tag with ID's posts
        $app->get('/:id/posts', 'authenticateKey', function($id) use ($app) {
            $result = array();
            $userID = $app->request->headers->get("Php-Auth-User");  

            $sql = "SELECT DISTINCT Posts.*, COALESCE(pv.value, 0) as voteValue, Users.name FROM Posts
                    JOIN Users ON Users.ID = Posts.authorID
                    JOIN PostTags ON Posts.ID = PostTags.postID
                    LEFT JOIN (SELECT * FROM PostVotes WHERE PostVotes.userID = :userID) as pv
                    ON Posts.ID = pv.postID
                    WHERE PostTags.tagID = :ID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":userID", $userID);
                $stmt->bindParam("ID", $id);
                $stmt->execute();

                foreach($stmt as $row) {
                    $stmt2 = $db->prepare($sql2);
                    $stmt2->bindParam(":postID", $row['ID'], PDO::PARAM_INT);
                    $stmt2->execute();
                    $tags = array();
                    foreach($stmt2 as $row2) {
                        $tags[] = array("name" => $row2['name'], "id" => $row2['ID']);       
                    }
                    $timeInterval = getTimeInterval($row['dateTime']);
                    $result[] = array(
                        "ID" => $row['ID'],
                        "authorName" => $row['name'],
                        "authorID" => $row['authorID'],
                        "votes" => $row['votes'],
                        "voteValue" => $row['voteValue'],
                        "title" => $row['title'],
                        "link" => $row['link'],
                        "body" => $row['body'],
                        "numComments" => $row['numComments'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $timeInterval,             
                        "tags" => $tags
                    );
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
            echo json_encode($result);    
        });

        // Add a tag
        $app->post('/', function() use ($app) {

        });
    });

    // Subscription group
    $app->group('/subscriptions', function() use ($app) {
        // Add a subscription
        $app->post('/', 'authenticateKey', function() use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true); 
            $userID = $app->request->headers->get("Php-Auth-User"); 

            $exists = "SELECT COUNT(*) as subExists FROM Subscriptions 
                    WHERE userID = :userID AND tagID = (
                    SELECT id FROM Tags WHERE Tags.name = :tagName)";
            $sql = "INSERT INTO Subscriptions (tagID, userID, onTop)
                    SELECT Tags.id, :userID, 0 FROM Tags
                    WHERE Tags.id = (
                    SELECT id FROM Tags WHERE Tags.name = :tagName);
                    UPDATE Tags SET usercount=usercount+1 WHERE Tags.name = :tagName";

            try {  
                $db = getConnection();
                $stmt = $db->prepare($exists);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->bindParam(":tagName", $data['tagName']);
                $stmt->execute();
                $row = $stmt->fetch();

                if($row['subExists'] == 0) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                    $stmt->bindParam(":tagName", $data['tagName']);
                    $stmt->execute();
                    $app->response->setStatus(201);
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }   
        });

        // Update subscription with tagID
        $app->put('/:id', 'authenticateKey', function($id) use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true); 
            $userID = $app->request->headers->get("Php-Auth-User"); 

            $sql = "UPDATE Subscriptions SET onTop = :onTop WHERE tagID = :tagID AND userID = :userID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":onTop", $data['onTop'], PDO::PARAM_INT);
                $stmt->bindParam(":tagID", $id, PDO::PARAM_INT);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->execute();
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });

        // Delete subscription with tagID
        $app->delete('/:id', 'authenticateKey', function($id) use ($app) {
            $userID = $app->request->headers->get("Php-Auth-User"); 

            $exists = "SELECT COUNT(*) as subExists FROM Subscriptions 
                    WHERE userID = :userID AND tagID = :tagID";
            $sql = "DELETE FROM Subscriptions WHERE tagID = :tagID AND userID = :userID;
                    UPDATE Tags SET usercount=usercount-1 WHERE ID = :tagID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($exists);
                $stmt->bindParam(":tagID", $id, PDO::PARAM_INT);
                $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch();

                if($row['subExists'] >= 1) {
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(":tagID", $id, PDO::PARAM_INT);
                    $stmt->bindParam(":userID", $userID, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });
    });

});


// Authenticate user based on ID and apiKey, update time
function authenticateKey() {
    $app = \Slim\Slim::getInstance();

    $userID = $app->request->headers->get("Php-Auth-User");
    $apiKey = $app->request->headers->get("Php-Auth-Pw");

    $sql = "SELECT COUNT(*) as keyExists, apiKey, dateTime FROM APIKeys WHERE userID = :ID";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql); 
        $stmt->bindParam(":ID", $userID);
        $stmt->execute();
        $row = $stmt->fetch();
    } catch(Exception $e) {
        $app->response->setStatus(500);
        echo $e;
    }
    $timediff = strtotime(getTime()) - strtotime($row['dateTime']);

    // If not logged in or key out of date
    if($apiKey !== $row['apiKey'] || $timediff > 3600) {
        $app->halt(401);
    }  
};

function getTime() {
    $now = new DateTime(null, new DateTimeZone('America/New_York'));
    $now = $now->format("Y-m-d H:i:s");
    return $now;
}

function getTimeInterval($dateTime) {
    $currentTime = strtotime(getTime());
    $time = strtotime($dateTime);
    $minsOld = ($currentTime - $time) / 60;

    if($minsOld < 10) {
        return "just now";
    } else if($minsOld < 60) {
        return "less than an hour ago";
    } else if($minsOld < 1440) {
        $hours = (int) round($minsOld/60);
        if($hours > 1) {
            return "$hours hours ago";
        }
        return "$hours hour ago";
    } else if($minsOld < 43200) {
        $days = (int) round($minsOld/1440);
        if($days > 1) {
            return "$days days ago";
        }
        return "$days day ago"; 
    } else if($minsOld < 525949) {
        $months = (int) round($minsOld/43200);
        if($months > 1) {
            return "$months months ago";
        }
        return "$months month ago";
    } else {
        $years = (int) round($minsOld/525949);
        if($years > 1) {
            return "$years years ago";
        }
        return "$years year ago";
    }

    return "eons ago";
}

function getConnection() {
    $dbhost = "127.0.0.1";
    $dbuser = "mcgrail_group5";
    $dbpass = "f1v3@l1v3";
    $dbname = "mcgrail_group5";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}

$app->run();
