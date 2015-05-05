<?php

require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() {
    echo 'Welcome to Taggit.';
});


// API group //
$app->group('/api', function() use ($app) {
    // Users group
    $app->group('/users', function() use ($app) {
        $app->get('/', function () {
            echo "All users";     
        });

        $app->get('/:id', function ($id) {
            $result = array(
                array(
                    "username" => "donkusking",
                    "score" => 10,
                    "tagNames" => array("tag1", "tag2"),
                    "tagIDs" => array(101, 102)
                    ),
                    array(
                    "username" => "donkusfranchi",
                    "score" => 123,
                    "tagNames" => array("tag2", "tag3"),
                    "tagIDs" => array(102, 103)
                    )
            );
            echo $result;
        });

        $app->get('/:id/posts', function ($id) {
            $result = array(
                array(
                    "authorName" => "donkusking",
                    "votes" => 12,
                    "title" => "New song by Wolf Platoon",
                    "body" => "It's pretty great, IMO. Check it out on torrent trackers near you.",
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tagNames" => array("tag1", "tag2"),
                    "tagIDs" => array(101, 102)
                    ),
                array(
                    "authorName" => "donkusking",
                    "votes" => -10,
                    "title" => "Wolf Platoon no longer a band",
                    "body" => "Looks like piracy got em, sorry boys.",
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tagNames" => array("tag2", "tag3"),
                    "tagIDs" => array(102, 103)
                    )       
            );
            echo json_encode($result);
        });

        $app->get('/:id/frontpage', function ($id) {
            $result = array(
                array(
                    "authorName" => "donkuslord",
                    "authorID" => 1,
                    "votes" => 13,
                    "title" => "I am donkuslord",
                    "body" => "lord of donks",
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tagNames" => array("tag1", "tag2"),
                    "tagIDs" => array(101, 102)
                    ),
                array(
                    "authorName" => "donkusking",
                    "authorID" => 2,
                    "votes" => 1,
                    "title" => "I am donkusking",
                    "body" => "king of donks",
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tagNames" => array("tag2", "tag3"),
                    "tagIDs" => array(102, 103)
                    )       
            );
            echo json_encode($result);          
        });

        $app->get('/:id/comments', function ($id) {
            $result = array(
                array(
                    "authorID" => 1,
                    "authorName" => "donkusking",
                    "body" => "This comment sucks",
                    "editedOn" => "2015-05-04 06:12:12",
                    "dateTime" => "2015-05-04 05:12:12"
                    ),
                    array(
                    "authorID" => 1,
                    "authorName" => "donkusking",
                    "body" => "This comment really sucks",
                    "editedOn" => "",
                    "dateTime" => "2015-05-02 06:13:10"
                    )
            ); 
            echo json_encode($result);      
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
            $result = array(
                array(
                    "authorID" => 2,
                    "authorName" => "donkusfranchi",
                    "votes" => 142,
                    "title" => "gr8postm8",
                    "body" => "this post is gr8, gr8 gr8 gr8 gr8 gr8 gr8 gr8",
                    "editedOn" => "",
                    "dateTime" => "2015-05-2 06:13:09",
                    "tagNames" => array("tag2", "tag3"),
                    "tagIDs" => array(102, 103)                    
                    )
            ); 
            echo json_encode($result);               
        });

        $app->get('/:id/comments', function ($id) {
            $result = array(
                array(
                    "authorID" => 1,
                    "authorName" => "donkusfranchi",
                    "body" => "comment time deluxe",
                    "editedOn" => "",
                    "dateTime" => "2015-05-02, 06:13:09"                   
                    ),
                array(
                    "authorID" => 3,
                    "authorName" => "donkuslord",
                    "body" => "comment time super deluxe",
                    "editedOn" => "",
                    "dateTime" => "2015-05-03, 06:13:09"                   
                    )
            ); 
            echo json_encode($result);             
        });

        $app->post('/', function() {

        });

        $app->put('/:id', function($id) {

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
            $result = array(
                array(
                    "tagName" => "tag1",
                    "tagID" => 101                
                    ),      
                    array(
                    "tagName" => "tag2",
                    "tagID" => 102                 
                    )   
            );  
            echo json_encode($result);         
        });

        $app->get('/:id/posts', function() {
            $result = array(
                array(
                    "authorName" => "donkuslord",
                    "authorID" => 1,
                    "votes" => 13,
                    "title" => "I am donkuslord",
                    "body" => "lord of donks",
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tagNames" => array("tag1", "tag2"),
                    "tagIDs" => array(101, 102)
                    ),
                array(
                    "authorName" => "donkusking",
                    "authorID" => 2,
                    "votes" => 1,
                    "title" => "I am donkusking",
                    "body" => "king of donks",
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tagNames" => array("tag2", "tag3"),
                    "tagIDs" => array(102, 103)
                    )       
            );
            echo json_encode($result); 
        });

        $app->post('/', function() {

        });
    });
});

$app->run();
