angular.module('taggit')
.constant('rootUrl', 'api/v1')
.controller('loginController', function($scope, $http, $location, rootUrl, UserService){
	$scope.login = function() {
		$scope.loginError = false;
		var request = {email:$scope.email, passwordHash:$scope.password};
		
		$http.post(rootUrl + '/login', request).
			success(function(data) {
                var response = angular.fromJson(data);
                UserService.updateUserID(response["userID"]);
                UserService.updateApiKey(response["apiKey"]);
                UserService.setLoggedIn();
				
                $location.path("/");
			}).
			error(function(data) {
				$scope.loginError = true;
			});
	}
})
.controller('registerController', function($scope, $http, $location, rootUrl, UserService){
    $scope.register = function() {
        $scope.emailError = false;
        $scope.emailFormatError = false;
        $scope.usernameError = false;
        $scope.passwordError = false;
        var request = {email:$scope.email, name:$scope.username, passwordHash:$scope.password1};

        if($scope.password1 === $scope.password2) {
            $http.post(rootUrl + '/users', request).
                success(function(data) {
                    $location.path("/login");
                }).
                error(function(data) {
                    var response = angular.fromJson(data);
                    $scope.emailError = response["emailError"];
                    $scope.emailFormatError = response["emailFormatError"];
                    $scope.usernameError = response["usernameError"];
                });
        } else {
            $scope.passwordError = true;
        }
    }
})
.controller('frontController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-front';
    $http.get(rootUrl + '/users/'+UserService.userID+'/frontpage').
    success(function(data) {
        $scope.posts = data;
        console.log(data);
    });
})

.controller('new_postController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-new_post';   
})

.controller('user_postsController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-user_posts';   
})

.controller('user_tagController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-user_tags';   
})

.controller('user_commentsController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-user_comments';   
})

.controller('tagController', function($scope, $http, rootUrl, UserService){
    $scope.pageClass = 'page-tag';   
})

.controller('taggitController', function($scope, $http, $location, rootUrl, UserService) {
    $scope.$on("loginStatusChanged", function() {
        $scope.showNav = UserService.loggedIn;
        console.log(UserService.loggedIn);

        $http.get(rootUrl + '/users/' + UserService.userID).
        success(function(data) {
            $scope.user = data;
        });
    })
 

    $scope.selectedIndex = 0; // Whatever the default selected index is, use -1 for no selection

    //    $scope.itemClicked = function ($index) {
    //        $scope.selectedIndex = $index;
    //    };

    $scope.logout = function() {        
        $http.post(rootUrl + '/logout').
            success(function(data) {
                UserService.updateUserID(0);
                UserService.updateApiKey("");
                UserService.setLoggedOut();
                
                $location.path("/login");
            })
    }
})

.controller('postController', function($scope, $http, rootUrl, UserService) {
    $scope.upvote = function() {
        if($scope.post.voteValue == 0) {
            $scope.sendVote(1);
            $scope.post.voteValue = 1;
            $scope.post.votes = parseInt($scope.post.votes)+1;
        } else if ($scope.post.voteValue == -1){
            $scope.sendVote(1);
            $scope.post.voteValue = 1 
            $scope.post.votes = parseInt($scope.post.votes)+2;
        } else {
            $scope.sendVote(0);
            $scope.post.voteValue = 0;
            $scope.post.votes -= 1;
        }
    }

    $scope.downvote = function() {
        if($scope.post.voteValue == 0) {
            $scope.sendVote(-1);
            $scope.post.voteValue = -1 
            $scope.post.votes -= 1;
        } else if ($scope.post.voteValue == 1){
            $scope.sendVote(-1);
            $scope.post.voteValue = -1 
            $scope.post.votes -= 2;
        } else {
            $scope.sendVote(0);
            $scope.post.voteValue = 0;
            $scope.post.votes = parseInt($scope.post.votes)+1;
        }
    }

    $scope.sendVote = function(voteValue) {
        var request = {value:voteValue};
        $http.put(rootUrl + '/posts/' + $scope.post.ID + '/vote', request).
            success(function(data) {

            }).
            error(function(data) {

            });
    }
});