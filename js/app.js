angular.module('taggit', [
    'ngRoute'
])
    .config(function($httpProvider){
    $httpProvider.interceptors.push('authInterceptor')
})
    .config(function($routeProvider, $locationProvider) {
    $routeProvider

    // route for the frontpage
        .when('/', {
        templateUrl : 'pages/front.html',
        controller  : 'frontController'
    })

    // route for the about page
        .when('/new_post', {
        templateUrl : 'pages/new_post.html',
        controller  : 'new_postController'
    })

    // route for the about page
        .when('/user_posts', {
        templateUrl : 'pages/user_posts.html',
        controller  : 'user_postsController'
    })

    // route for the about page
        .when('/user_tags', {
        templateUrl : 'pages/user_tags.html',
        controller  : 'user_tagController'
    })

    // route for the about page
        .when('/user_comments', {
        templateUrl : 'pages/user_comments.html',
        controller  : 'user_commentsController'
    })
    // Route for login page
        .when('/login', {
        templateUrl : 'pages/login.html',
        controller : 'loginController'
    })
    // Route for registration page
        .when('/register', {
        templateUrl : 'pages/register.html',
        controller : 'registerController'
    })

        .when('/tag',{
        templateUrl : 'page/tag.html',
        controller : 'tagController'
    })

    //$locationProvider.html5Mode(true);

}).run(function($rootScope, $location, UserService) {
    $rootScope.$on("$routeChangeStart", function(event, next, current) {
        if(UserService.loggedIn == false) {
            console.log("NOT LOGGED IN");
            // no logged user, redirect to login
            if(next.templateUrl !== "pages/login.html" && next.templateUrl !== "pages/register.html") {
                $location.path('/login');
            }
        }	
    });
});