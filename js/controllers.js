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

    .controller('new_postController', function($scope, $http, $location, rootUrl, UserService){
    $scope.submitPost = function() {
        // title, body, tags (array of tagnames)
        var tagList = $scope.tags.split(',');
        var i;
        // Force lowercase, trim spaces
        for(i = 0; i < tagList.length; i++) {
            tagList[i] = tagList[i].trim().toLowerCase();
        }

        var request = {title:$scope.title, body:$scope.body, tags:tagList};

        $http.post(rootUrl + '/posts', request).
        success(function() {
            $location.path("/");
        }).
        error(function() {
            $location.path("/");
        });
    }
})

// edit_post page controller
    .controller('edit_postController', function($scope, $http, $location, rootUrl, UserService){
    var postID = $location.search()['id'];

    // get old content
    $http.get(rootUrl + '/posts/'+postID).
    success(function(data){
        $scope.post = data[0];
        $scope.body = data[0].body;
        $scope.title = data[0].title;
    }).
    error(function(){
        $location.path("/");
    });

    // put new content
    $scope.edit = function(){
        var request = {title:$scope.title,body:$scope.body};
        $http.put(rootUrl + '/posts/'+postID, request).
        success(function(){
            $location.path("/my_posts");
            //            ng-href = "#comments?postID={{post.ID}}"
        });
    }
})

// user_posts page controller
    .controller('user_postsController', function($scope, $http, $location, rootUrl, UserService){
    userID = $location.search()['authorID'];
    $scope.userID = userID
    $scope.username = $location.search()['authorName'];
    $http.get(rootUrl + '/users/'+userID+'/posts').
    success(function(data){
        $scope.posts = data;
    });
})

    .controller('user_commentsController', function($scope, $http, $location, rootUrl, UserService){
    userID = $location.search()['authorID'];
    $scope.userID = userID
    $scope.username = $location.search()['authorName'];
    $http.get(rootUrl + '/users/'+userID+'/comments').
    success(function(data){
        $scope.comments = data;
    });
})



    .controller('frontController', function($scope, $http, rootUrl, UserService){
    $http.get(rootUrl + '/users/'+UserService.userID+'/frontpage').
    success(function(data) {
        $scope.posts = data;
    });
})





// controller for My Posts
    .controller('my_postsController', function($scope, $http, rootUrl, UserService){
    $http.get(rootUrl + '/users/'+UserService.userID+'/posts').
    success(function(data){
        $scope.posts = data;
    });

    // delete post with ID
    $scope.delete = function(postID){
        $http.delete(rootUrl + '/posts/'+postID).
        success(function(){
            console.log("POST DELETED WITH ID " + postID);   
        }).error(function(){
            console.log("POST DELETE UNSUCCESSFUL")   
        });
    }
})


// controller for My Comments
    .controller('my_commentsController', function($scope, $http, rootUrl, UserService){
    $http.get(rootUrl + '/users/'+UserService.userID+'/comments').
    success(function(data){
        $scope.comments = data;
    });        
})


// controller for My Tags
    .controller('my_tagsController', function($scope, $http, rootUrl, UserService){
    $http.get(rootUrl + '/users/'+UserService.userID+'/subscriptions').
    success(function(data){
        $scope.subscriptions = data;
    });


    // unsubscribe to tag with ID
    $scope.unsubscribe = function(tagID){
        $http.delete(rootUrl + '/subscriptions/'+tagID).
        success(function(){

            $http.get(rootUrl + '/users/'+UserService.userID+'/subscriptions').
            success(function(data){
                $scope.subscriptions = data;

            });

            $http.get(rootUrl + '/users/' + UserService.userID).
            success(function(data) {
                $scope.$parent.topTags = [];
                $scope.$parent.user = data[0];
                for(var tag in data[0].tags) {
                    var obj = data[0].tags[tag];
                    if(obj.top == 1) {
                        $scope.$parent.topTags.push(obj);
                    }
                }
            });            
        });
    }

    $scope.putOnTop = function(tagID){
        var request = {onTop:1}
        $http.put(rootUrl + '/subscriptions/'+tagID,request).
        success(function(){
            $http.get(rootUrl + '/users/' + UserService.userID).
            success(function(data) {
                $scope.$parent.topTags = [];
                $scope.$parent.user = data[0];
                for(var tag in data[0].tags) {
                    var obj = data[0].tags[tag];
                    if(obj.top == 1) {
                        $scope.$parent.topTags.push(obj);
                    }
                }
            });   
        });
    }

    $scope.removeFromTop = function(tagID){
        var request = {onTop:0}
        $http.put(rootUrl + '/subscriptions/'+tagID,request).
        success(function(){
            
            $http.get(rootUrl + '/users/' + UserService.userID).
            success(function(data) {
                $scope.$parent.topTags = [];
                $scope.$parent.user = data[0];
                for(var tag in data[0].tags) {
                    var obj = data[0].tags[tag];
                    if(obj.top == 1) {
                        $scope.$parent.topTags.push(obj);
                    }
                }
            });   
        });
    }

})

// tag controller
    .controller('tagController', function($scope, $http, $location, rootUrl, UserService){
    var tagID = $location.search()['id'];
    var name = $location.search()['name'];
    $http.get(rootUrl + '/tags/' + tagID + '/posts').
    success(function(data) {
        $scope.posts = data;
    });

    $scope.subscribe = function(){
        var request = {tagName:name};
        $http.post(rootUrl + '/subscriptions',request).
        success(function(){ 
            $http.get(rootUrl + '/users/' + UserService.userID).
            success(function(data) {
                $scope.$parent.topTags = [];
                $scope.$parent.user = data[0];
                for(var tag in data[0].tags) {
                    var obj = data[0].tags[tag];
                    if(obj.top == 1) {
                        $scope.$parent.topTags.push(obj);
                    }
                }
            });   
        });
    }

})

// comments controller
    .controller('commentsController', function($scope, $http, $location, rootUrl, UserService){
    $scope.postData;
    $scope.comments;
    $scope.showDelete = false;
    var postID = $location.search()['postID'];

    //get post with ID
    $http.get(rootUrl + '/posts/' + postID).
    success(function(data) {
        $scope.postData = data;
    });

    // get comments for post with ID
    $http.get(rootUrl + '/posts/' + postID + '/comments').
    success(function(data) {
        $scope.comments = data;
    });

    // delete post with ID
    $scope.deletePost = function() {
        $http.delete(rootUrl + '/posts/' + postID).
        success(function() {
            $location.path("/");
        });
    }

    $scope.submitComment = function(postID){
        var request = {body:$scope.body};
        $http.post(rootUrl + '/posts/' + postID + '/comments', request).
        success(function(){
            $http.get(rootUrl + '/posts/' + postID + '/comments').
            success(function(data) {
                $scope.comments = data;
            });
        });
    }  
})


    .controller('taggitController', function($scope, $http, $location, rootUrl, UserService) {
    $scope.$on("loginStatusChanged", function() {
        $scope.showNav = UserService.loggedIn;
        $scope.topTags = [];

        $http.get(rootUrl + '/users/' + UserService.userID).
        success(function(data) {
            $scope.user = data[0];
            for(var tag in data[0].tags) {
                var obj = data[0].tags[tag];
                if(obj.top == 1) {
                    $scope.topTags.push(obj);
                }
            }
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