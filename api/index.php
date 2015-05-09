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
            $sql2 = "SELECT Tags.name, Tags.id FROM Subscriptions, Tags 
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
                        $tags[] = array("name" => $row2['name'], "id" => $row2['id']);
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

        });

        $app->get('/:id/frontpage', function ($id) {
    
        });

        $app->get('/:id/comments', function ($id) {
    
        });

        $app->post('/', function() {

        });

        $app->put('/:id', function($id) {

        });
    });

    // Posts group
    $app->group('/posts', function() use ($app) {
        $app->get('/', function () {
            
        });

        $app->get('/:id', function ($id) {
            
        });

        $app->get('/:id/comments', function ($id) {
          
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

        });

        $app->get('/:id/posts', function() {

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
