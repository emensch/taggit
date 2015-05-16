var taggit = angular.module('taggit', []);

taggit.controller('taggitController', function($scope, $http) {
	rootUrl = 'api/v1/';
	userID = 1;
	postID = 1;

	$http.get(rootUrl + 'users/'+userID).
		success(function(data) {
			$scope.user = data;
		});

	$http.get(rootUrl + 'users/'+userID+'/frontpage').
		success(function(data) {
			$scope.posts = data;
			console.log($scope.posts);
		});

	$http.get(rootUrl + 'posts/'+postID+'/comments').
		success(function(data) {
			$scope.comments = data;
			console.log($scope.comments);
		});
})