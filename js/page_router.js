
// create the module and name it scotchApp
var pageRouter = angular.module('pageRouter', ['ngRoute']);

// configure our routes
pageRouter.config(function($routeProvider) {
    $routeProvider

    // route for the frontpage
        .when('/', {
        templateUrl : 'pages/front.html',
        controller  : 'taggitController'
    })

    // route for the about page
        .when('/new_post', {
        templateUrl : 'pages/new_post.html',
        controller  : 'taggitController'
    })
});

angular.bootstrap(document.getElementById("pageRouter"), ['ngRoute']);