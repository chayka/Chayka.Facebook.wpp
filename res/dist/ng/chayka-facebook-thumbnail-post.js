"use strict";angular.module("chayka-facebook-thumbnail-post",["chayka-forms","chayka-nls","chayka-wp-admin","chayka-utils","chayka-facebook-thumbnail-generator"]).directive("postThumbnailEditor",["utils",function(a){return{templateUrl:a.getResourceUrl("facebook","ng/chayka-facebook-thumbnail-post.html"),scope:{fonts:"=?",defaultFont:"@?",defaultLogo:"@?",defaultBackground:"@?",templates:"=?",customTemplate:"=?",layout:"=?"},controller:["$scope","modals","ajax",function(a,b,c){var d=window.Chayka.Facebook.ThumbnailGenerator;angular.extend(a,d),angular.extend(a,{isGeneratorSetUp:function(){return!!a.fonts&&a.defaultFont&&a.templates},getLayoutOptions:function(){var b={featured:"Featured Image"};for(var c in a.templates)a.templates.hasOwnProperty(c)&&(b[c]=a.templates[c].name);return a.customTemplate&&(b.custom="Custom Layout"),b}})}]}}]);