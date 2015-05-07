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
})