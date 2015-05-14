var taggit = angular.module('taggit', []);

taggit.controller('taggitController', function($scope, $http) {
    rootUrl = '/api/v1';
    userID = 1;

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
        if(item.voted <= 0) {
            item.voted = 1;   
        } else {
            item.voted = 0;   
        }

    }

    $scope.downvote = function(item) {
        if(item.voted >= 0) {
            item.voted = -1   
        } else {
            item.voted = 0;   
        }
    }

    $scope.toggle = function(item){
        item.selected = !item.selected;
    }

})