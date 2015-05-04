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
            echo "User with $id";
        });

        $app->get('/:id/posts', function ($id) {
            // Dummy data
            $result = array(
                array(
                    "ID" => 1, "userID" => 10, "title" => "Post 1", "link" => "https://www.google.com/",
                    "body" => "Lorem ipsum dolor sit whateverthefuck", "commentCount" => 3, 
                    "dateTime" => "2015-05-04 12:20:13"
                    ),
                array(
                    "ID" => 2, "userID" => 10, "title" => "Post 2", "link" => "https://www.google.com/",
                    "body" => "Lorem ipsum dolor sit whateverthefuck", "commentCount" => 2, 
                    "dateTime" => "2015-05-03 12:22:13"
                    )        
            );
            echo json_encode($result);
        });

        $app->get('/:id/frontpage', function ($id) {
            // Dummy data
            $result = array(
                array(
                    "ID" => 1, "userID" => 10, "title" => "Post 1", "link" => "https://www.google.com/",
                    "body" => "Lorem ipsum dolor sit whateverthefuck", "commentCount" => 3, 
                    "dateTime" => "2015-05-04 12:20:13"
                    ),
                array(
                    "ID" => 2, "userID" => 10, "title" => "Post 2", "link" => "https://www.google.com/",
                    "body" => "Lorem ipsum dolor sit whateverthefuck", "commentCount" => 2, 
                    "dateTime" => "2015-05-03 12:22:13"
                    )        
            );
            echo json_encode($result);            
        });

        $app->get('/:id/tags', function ($id) {
            // Dummy data
            $result = array(
                array(
                    "ID" => 1, "name" => "tag1"
                    ),
                array(
                    "ID" => 2, "name" => "tag2"
                    )        
            );
            echo json_encode($result);           
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

        $app->get('/id', function ($id) {
            
        });

        $app->get('/id/comments', function ($id) {
            
        });

        $app->get('/id/tags', function ($id) {
            
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
        $app->get('/', function () {
            
        });

        $app->post('/', function() {

        });
    });
});

$app->run();
