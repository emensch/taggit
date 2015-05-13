<?php

require '../Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() {
    
});

// v1 group 
$app->group('/v1', function() use ($app) {
    // Users group
    $app->group('/users', function() use ($app) {
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
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);
        });

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
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);
        });

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
                        "dateTime" => $row['dateTime'],             
                        "tags" => $tags
                    );
                }
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);
        });

        $app->get('/:id/frontpage', function ($id) {
            $result = array();
            $sql = "SELECT DISTINCT Posts.*, Users.name
                    FROM Posts, Subscriptions, Tags, PostTags, Users
                    WHERE Posts.ID = PostTags.postID
                    AND PostTags.tagID = Tags.ID
                    AND Tags.ID = Subscriptions.tagID
                    AND Subscriptions.userID = :ID
                    AND Users.ID = Posts.authorID
                    ORDER BY Posts.Votes DESC";
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
                        "dateTime" => $row['dateTime'],             
                        "tags" => $tags
                    );
                }    

            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);            
        });

        $app->get('/:id/comments', function ($id) {
            $result = array();
            $sql = "SELECT Comments.*, Users.name FROM Comments, Users 
                    WHERE Comments.authorID = :ID AND Users.ID = Comments.authorID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $result[] = array(
                        "ID" => $row['id'],
                        "parentID" => $row['postID'],
                        "authorID" => $row['authorID'],
                        "authorName" => $row['name'],
                        "body" => $row['body'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $row['dateTime']
                    );
                }

            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);
        });

        $app->post('/', function() {

        });

        $app->put('/:id', function($id) {

        });
    });

    // Posts group
    $app->group('/posts', function() use ($app) {
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
                        "dateTime" => $row['dateTime'],             
                        "tags" => $tags
                    );
                }
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);         
        });

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
                    "dateTime" => $row['dateTime'],             
                    "tags" => $tags
                );
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);              
        });

        $app->get('/:id/comments', function ($id) {
            $result = array();
            $sql = "SELECT Comments.*, Users.name FROM Comments, Users 
                    WHERE Comments.postID = :ID AND Users.ID = Comments.authorID";

            try {
                $db = getConnection();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(":ID", $id, PDO::PARAM_INT);
                $stmt->execute();

                foreach($stmt as $row) {
                    $result[] = array(
                        "ID" => $row['id'],
                        "parentID" => $row['postID'],
                        "authorID" => $row['authorID'],
                        "authorName" => $row['name'],
                        "body" => $row['body'],
                        "editedOn" => $row['editedOn'],
                        "dateTime" => $row['dateTime']
                    );
                }

            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);          
        });

        $app->post('/', function() {

        });

        $app->put('/:id', function($id) {

        });

        $app->put('/:id/votes', function($id) {

        });

        $app->delete('/', function($id) {

        });
    });

    // Comments group
    $app->group('/comments', function() use ($app) {
        $app->post('/', function() {

        }); 

        $app->put('/:id', function($id) {

        }); 

        $app->delete('/:id', function($id) {

        });
    });

    // Tags group
    $app->group('/tags', function() use ($app) {
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
            } catch(PDOException $e) {
                echo $e;
            }   
        });

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
                        "dateTime" => $row['dateTime'],             
                        "tags" => $tags
                    );
                }
            } catch(PDOException $e) {
                echo $e;
            }
            echo json_encode($result);    
        });

        $app->post('/', function() {

        });
    });
});

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
