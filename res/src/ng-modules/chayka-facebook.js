'use strict';
angular.module('chayka-auth')
    .directive('authFacebookButton', ['$translate', 'ajax', function($translate, ajax){
        var $ = angular.element;
        var fb = {

            $scope: null,

            notAuthorized: false,
            FB: window.FB,
            currentUser: window.Chayka.Users.currentUser,

            getFB: function(){
                if(!fb.FB && window.FB){
                    // Here we subscribe to the auth.authResponseChange JavaScript event. This event is fired
                    // for any authentication related change, such as login, logout or session refresh. This means that
                    // whenever someone who was previously logged out tries to log in again, the correct case below
                    // will be handled.
                    fb.FB = window.FB;
                    fb.FB.Event.subscribe('auth.authResponseChange', fb.onStatusChanged);
                    //this.parseXFBML();
                }
                return fb.FB;
            },

            logout: function(){
                if(fb.getFB() && fb.getFbUserId() && !fb.notAuthorized){
                    fb.currentUser.meta.fb_user_id = null;
                    fb.getFB().logout();
                }
            },

            getFbUserId: function(){
                return fb.currentUser.meta.fb_user_id;
            },

            //onFacebookLoginButtonClicked: function(event){
            //    event.preventDefault();
            //    var fb = this.getFB();
            //    if(fb){
            //        fb.getLoginStatus($.proxy(this.onStatusChanged, this));
            //    }
            //},

            onStatusChanged: function(response) {
                // Here we specify what we do with the response anytime this event occurs.
                if (response.status === 'connected') {
                    // The response object is returned with a status field that lets the app know the current
                    // login status of the person. In this case, we're handling the situation where they
                    // have logged in to the app.
                    // testAPI();
                    if(fb.getFbUserId() !== response.authResponse.userID) {
                        fb.onFbLogin(response);
                    }
                } else if (response.status === 'not_authorized') {
                    // In this case, the person is logged into Facebook, but not into the app, so we call
                    // FB.login() to prompt them to do so.
                    // In real-life usage, you wouldn't want to immediately prompt someone to login
                    // like this, for two reasons:
                    // (1) JavaScript created popup windows are blocked by most browsers unless they
                    // result from direct interaction from people using the app (such as a mouse click)
                    // (2) it is a bad experience to be continually prompted to login upon page load.
//                FB.login();
                    fb.notAutorized = true;
                    //if(fb.getFbUserId()) {
                    //    //fb.buttonLogoutClicked();
                    //}
                } else {
                    // In this case, the person is not logged into Facebook, so we call the login()
                    // function to prompt them to do so. Note that at this stage there is no indication
                    // of whether they are logged into the app. If they aren't then they'll see the Login
                    // dialog right after they log in to Facebook.
                    // The same caveats as above apply to the FB.login() call here.
                    fb.notAutorized = true;
                    //if(fb.getFbUserId()){
                    //    //fb.buttonLogoutClicked();
                    //}
//                FB.login();
                }
            },

            onLoginButtonClicked: function(event){
                if(event) {
                    event.preventDefault();
                }
                fb.getFB().login(function(response){}, {scope:'public_profile,email'});
            },

            onFbLogin: function(FBResponse){
                console.dir({FBResponse: FBResponse});
                ajax.post('/api/facebook/login', FBResponse.authResponse, {
                    spinner: false,
                    showMessage: false,
                    errorMessage: $translate.instant('message_error_auth_failed'),
                    success: function(data){
                        //console.dir({'data': data});
                        fb.$scope.$emit('Chayka.Users.currentUserChanged', data.payload);
                        //angular.extend(fb.currentUser, data.payload);
                        //$(document).trigger('userChanged', data.payload);
                        //$(document).trigger('Facebook.Auth.userLoggedIn');
                    },
                    complete: function(data){
                    }
                });
            }
        };

        return {
            restrict: 'A',
            link: function($scope, element){
                fb.getFB();
                fb.$scope = $scope;
                $(document).on('logout', fb.logout);
                $(element).click(fb.onLoginButtonClicked);
            }
        };
    }])
;