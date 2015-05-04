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
            echo "User with ID: $id";
        });

        $app->get('/:id/posts', function ($id) {
           echo "User with ID: $id's posts"; 
        });

        $app->get('/:id/frontpage', function ($id) {
            
        });

        $app->get('/:id/tags', function ($id) {
            
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
