"use strict";angular.module("chayka-auth").directive("authFacebookButton",["$translate","ajax",function($translate,ajax){var $=angular.element,fb={notAuthorized:!1,FB:window.FB,currentUser:window.Chayka.Users.currentUser,getFB:function(){return!fb.FB&&window.FB&&(fb.FB=window.FB,fb.FB.Event.subscribe("auth.authResponseChange",fb.onStatusChanged)),fb.FB},logout:function(){fb.getFB()&&fb.getFbUserId()&&!fb.notAuthorized&&(fb.currentUser.meta.fb_user_id=null,fb.getFB().logout())},getFbUserId:function(){return fb.currentUser.meta.fb_user_id},onStatusChanged:function(response){"connected"===response.status?fb.getFbUserId()!==response.authResponse.userID&&fb.onFbLogin(response):fb.notAutorized="not_authorized"===response.status?!0:!0},onLoginButtonClicked:function(event){event&&event.preventDefault(),fb.getFB().login(function(){},{scope:"public_profile,email"})},onFbLogin:function(FBResponse){console.dir({FBResponse:FBResponse}),ajax.post("/api/facebook/login",FBResponse.authResponse,{spinner:!1,showMessage:!1,errorMessage:$translate.instant("message_error_auth_failed"),success:function(data){console.dir({data:data}),angular.extend(fb.currentUser,data.payload),$(document).trigger("userChanged",data.payload),$(document).trigger("Facebook.Auth.userLoggedIn")},complete:function(){}})}};return{restrict:"A",link:function($scope,element){fb.getFB(),$(document).on("logout",fb.logout),$(element).click(fb.onLoginButtonClicked)}}}]);