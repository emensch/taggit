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

        .when('/new_post', {
        templateUrl : 'pages/new_post.html',
        controller  : 'new_postController'
    })

        .when('/my_posts', {
        templateUrl : 'pages/my_posts.html',
        controller  : 'my_postsController'
    })

        
        .when('/my_comments', {
        templateUrl : 'pages/my_comments.html',
        controller  : 'my_commentsController'
    })
    
    
        .when('/my_tags', {
        templateUrl : 'pages/my_tags.html',
        controller  : 'my_tagsController'
    })

        
        .when('/user_posts', {
        templateUrl : 'pages/user_posts.html',
        controller  : 'user_postsController'
    })

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
        templateUrl : 'pages/tag.html',
        controller : 'tagController'
    })

        .when('/comments',{
        templateUrl : 'pages/comments.html',
        controller : 'commentsController'
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