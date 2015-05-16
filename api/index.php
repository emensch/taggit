<?php

require '../Slim/Slim.php';
require 'password.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() {
    echo "lol neat";
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
                WHERE name = :name";
        $apikey = "INSERT APIKeys (userID, apiKey, dateTime)  
                VALUES (:userID, :key, :dateTime) 
                ON DUPLICATE KEY UPDATE apiKey = :key, dateTime = :dateTime";

        try {
            // Check credentials
            $db = getConnection();
            $stmt = $db->prepare($validate);
            $stmt->bindParam(":name", $data['name']);
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

    });

    $app->post('/logout', 'authenticateKey', function() use ($app) {
        $json = $app->request->getBody();
        $data = json_decode($json, true);
        $sql = "DELETE FROM APIKeys WHERE userID = :ID";

        try {
            $db = getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":ID", $data['userID']);
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
        $app->get('/:id', function ($id) {
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
        $app->get('/:id/posts', function ($id) {
            $result = array();
            $sql = "SELECT Posts.*, Users.name FROM Posts, Users WHERE Posts.authorID = :ID AND Users.ID = Posts.authorID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
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
        $app->get('/:id/frontpage', function ($id) {
            $result = array();
            $sql = "SELECT DISTINCT Posts.*, Users.name
                    FROM Posts, Subscriptions, Tags, PostTags, Users
                    WHERE Posts.ID = PostTags.postID
                    AND PostTags.tagID = Tags.ID
                    AND Tags.ID = Subscriptions.tagID
                    AND Subscriptions.userID = :ID
                    AND Users.ID = Posts.authorID
                    ORDER BY Posts.Votes DESC, DateTime DESC";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
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
        $app->get('/:id/comments', function ($id) {
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
        $app->put('/:id', function($id) use ($app) {

        });
    });

    // Posts group
    $app->group('/posts', function() use ($app) {
        // Get all posts
        $app->get('/', function() {
            $result = array();
            $sql = "SELECT Posts.*, Users.name FROM Posts, Users WHERE Users.ID = Posts.authorID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try { 
                $db = getConnection();
                $stmt = $db->prepare($sql);
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
        $app->get('/:id', function ($id) {
            $result = array();
            $sql = "SELECT Posts.*, Users.name FROM Posts, Users WHERE Posts.ID = :ID AND Users.ID = Posts.authorID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
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
        $app->get('/:id/comments', function ($id) {
            $result = array();
            $sql = "SELECT Comments.*, Users.name FROM Comments, Users 
                    WHERE Comments.postID = :ID AND Users.ID = Comments.authorID";

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

        // Add a post
        $app->post('/', function() use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);    

            $sql = "INSERT INTO Posts (authorID, title, link, body, dateTime) 
                    VALUES (:authorID, :title, :link, :body, :dateTime)";
            $sql2 = "INSERT IGNORE INTO Tags (name, userCount)
                    VALUES (:tagName, 0);
                    INSERT INTO PostTags (tagID, postID)
                    SELECT Tags.id, :postID FROM Tags
                    WHERE Tags.name = :tagName";
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":authorID", $data['authorID'], PDO::PARAM_INT);
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
        $app->put('/:id', function($id) use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $sql = "UPDATE Posts
                    SET title = :title, body = :body, editedOn = :editedOn
                    WHERE Posts.id = :id";
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":title", $data['title']);
                $stmt->bindParam(":body", $data['body']);
                $stmt->bindParam(":editedOn", getTime());
                $stmt->bindParam("id", $id);
                $stmt->execute();
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        });

        // Update/create vote
        $app->put('/:id/votes', function($id) use ($app) {
            /*
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $voteQ = "SELECT COUNT(*), up FROM PostVotes WHERE userID = :userID AND postID = :postID";

            try {
                // Check if user has already voted, store vote value (for correct incr/decr calc)
                $db = getConnection();
                $stmt = $db->prepare($voteQ);
                $stmt = 
            }
            */
        });

        // Delete post with ID
        $app->delete('/:id', function($id) {

        });
    });

    // Comments group
    $app->group('/comments', function() use ($app) {
        // Add a comment
        $app->post('/', function() use ($app) {
            $json = $app->request->getBody();
            $data = json_decode($json, true); 

            $sql = "INSERT INTO Comments (postID, authorID, body, dateTime)
                    VALUES (:postID, :authorID, :body, :dateTime);
                    UPDATE Posts SET numComments=numComments+1 WHERE ID = :postID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":postID", $data['postID'], PDO::PARAM_INT);
                $stmt->bindParam(":authorID", $data['authorID'], PDO::PARAM_INT);
                $stmt->bindParam(":body", $data['body']);
                $stmt->bindParam(":dateTime", getTime());
                $stmt->execute();
            } catch(Exception $e) {
                $app->response->setStatus(500);
                echo $e;
            }
        }); 

        // Update comment with ID
        $app->put('/:id', function($id) use ($app) {

        }); 

        // Delete comment with ID
        $app->delete('/:id', function($id) {

        });
    });

    // Tags group
    $app->group('/tags', function() use ($app) {
        // Get all tags
        $app->get('/', function() {
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
        $app->get('/:id/posts', function($id) {
            $result = array();
            $sql = "SELECT DISTINCT Posts.*, Users.name FROM Posts, Users, PostTags 
            WHERE Users.ID = Posts.authorID AND Posts.ID = PostTags.postID AND PostTags.tagID = :ID";
            $sql2 = "SELECT Tags.ID, Tags.name FROM Tags, PostTags
                    WHERE Tags.ID = PostTags.tagID
                    AND PostTags.postID = :postID";
                    
            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
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
});

// Authentication middleware function
function authenticateKey() {
    $app = \Slim\Slim::getInstance();

    $json = $app->request->getBody();
    $data = json_decode($json, true);
    $userID = $data['userID'];
    $apiKey = $data['apiKey'];

    $sql = "SELECT COUNT(*) as keyExists, apiKey FROM APIKeys WHERE userID = :ID";
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

    if($apiKey !== $row['apiKey']) {
        $app->redirect('/');
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
