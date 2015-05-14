<?php

require '../Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/', function() {
    echo "Welcome to Taggit!";
});

// v1 group //
$app->group('/v1', function() use ($app) {
    // Users group
    $app->group('/users', function() use ($app) {
        $app->get('/', function () {
            $result = array(
                array(
                    "username" => "donkusking",
                    "score" => 10,
                    "tags" => array(array("name" => "tag1", "id" => 102), array("name" => "tag1", "id" => 102))
                    ),
                    array(
                    "username" => "donkusfranchi",
                    "score" => 123,
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag2", "id" => 102))
                    )
            );
            echo json_encode($result);  
        });

        $app->get('/:id', function ($id) {
            $result = array(
                array(
                    "username" => "donkusking",
                    "score" => 10,
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag2", "id" => 102))
                    )
            );
            echo json_encode($result);
        });

        $app->get('/:id/posts', function ($id) {
            $result = array(
                array(
                    "authorName" => "donkusking",
                    "votes" => 12,
                    "title" => "New song by Wolf Platoon",
                    "body" => "It's pretty great, IMO. Check it out on torrent trackers near you.",
                    "numComments" => 7,
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tags" => array(array("name" => "tag3", "id" => 103), array("name" => "tag1", "id" => 101))
                    ),
                array(
                    "authorName" => "donkusking",
                    "votes" => -10,
                    "title" => "Wolf Platoon no longer a band",
                    "body" => "Looks like piracy got em, sorry boys.",
                    "numComments" => 3,
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tags" => array(array("name" => "tag2", "id" => 102), array("name" => "tag1", "id" => 101))
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
                    "voted" => 0,
                    "title" => "I am donkuslord: Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.awefawefawefawefawefawefawefawefawef",
                    "body" => "lord of donks",
                    "numComments" => 7,
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag2", "id" => 102))
                    ),
                array(
                    "authorName" => "donkusking",
                    "authorID" => 2,
                    "votes" => 1,
                    "voted" => -1,
                    "title" => "I am donkusking",
                    "body" => "king of donks",
                    "numComments" => 5,
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag3", "id" => 103))
                    ),       
                array(
                    "authorName" => "donkusmaster",
                    "authorID" => 2,
                    "votes" => 9001,
                    "voted" => 1,
                    "title" => "I am donkusmaster: Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.",
                    "body" => "master of donks",
                    "numComments" => 5,
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag3", "id" => 103))
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
                    "numComments" => 7,
                    "editedOn" => "",
                    "dateTime" => "2015-05-2 06:13:09",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag2", "id" => 102))                  
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
                    "title" => "I am donkuslord, lord of donks",
                    "body" => "lord of donks and things",
                    "numComments" => 7,
                    "editedOn" => "2015-05-03 12:22:13",
                    "dateTime" => "2015-03-02 12:23:34",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag2", "id" => 102))
                    ),
                array(
                    "authorName" => "donkusking",
                    "authorID" => 2,
                    "votes" => 1,
                    "title" => "I am donkusking",
                    "body" => "king of donks",
                    "numComments" => 7,
                    "editedOn" => "",
                    "dateTime" => "2015-03-04 14:13:22",
                    "tags" => array(array("name" => "tag1", "id" => 101), array("name" => "tag3", "id" => 103))
                    )       
            );
            echo json_encode($result); 
        });

        $app->post('/', function() {

        });
    });
});

$app->run();
