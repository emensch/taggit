var taggit = angular.module('taggit', []);

taggit.controller('taggitController', function($scope, $http) {
	rootUrl = '/api/v1'

	$http.get('/api').
		success(function(data) {
			$scope.greeting = data;
		});

	$http.get(rootUrl + '/users/1/frontpage').
		success(function(data) {
			$scope.posts = data;
			console.log($scope.posts);
		});
})