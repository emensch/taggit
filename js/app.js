var taggit = angular.module('taggit',
                            ['ngRoute',
                             'ngAnimate']);

taggit.config(function($routeProvider) {
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
});

taggit.controller('frontController', function($scope){
    $scope.pageClass = 'page-front';   
});

taggit.controller('new_postController', function($scope){
    $scope.pageClass = 'page-new_post';   
});

taggit.controller('taggitController', function($scope, $http) {
    rootUrl = '/api/v1';
    userID = 1;

    $scope.pageClass = 'page-front';   

    $http.get('/api/v1/users/'+userID).
    success(function(data) {
        $scope.user = data;
    });

    $http.get(rootUrl + '/users/'+userID+'/frontpage').
    success(function(data) {
        $scope.posts = data;
        console.log($scope.posts);
    });

    $scope.selectedIndex = 0; // Whatever the default selected index is, use -1 for no selection

    //    $scope.itemClicked = function ($index) {
    //        $scope.selectedIndex = $index;
    //    };

    $scope.upvote = function(item) {
        if(item.voted == 0) {
            item.voted = 1;
            item.votes += 1;
        } else if (item.voted == -1){
            item.voted = 1 
            item.votes += 2;
        } else {
            item.voted = 0;
            item.votes -= 1;
        }
    }

    $scope.downvote = function(item) {
        if(item.voted == 0) {
            item.voted = -1 
            item.votes -= 1;
        } else if (item.voted == 1){
            item.voted = -1 
            item.votes -= 2;
        } else {
            item.voted = 0;
            item.votes += 1;
        }
    }

});