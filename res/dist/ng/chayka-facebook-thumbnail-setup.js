"use strict";angular.module("chayka-facebook-thumbnail-setup",["chayka-forms","chayka-nls","chayka-wp-admin","chayka-utils","chayka-facebook-thumbnail-generator","lr.upload"]).directive("thumbnailSetup",["utils",function(a){return{templateUrl:a.getResourceUrl("facebook","ng/chayka-facebook-thumbnail-setup.html"),scope:{fonts:"=",defaultFont:"@?",defaultLogo:"@?",defaultBackground:"@?"},controller:["$scope","modals","ajax",function(b,c,d){var e=window.Chayka.Facebook.ThumbnailGenerator;angular.extend(b,e),angular.extend(b,{tab:"fonts",currentTemplate:null,temporaryTemplate:{},activateTab:function(a){b.tab=a},isTabActive:function(a){return b.defaultFont?a===b.tab:"fonts"===a},updateFontsFromPayload:function(a){b.fonts.splice(0,b.fonts.length);var c=a.fonts;c.forEach(function(a){b.fonts.push(a)}),b.defaultFont=a.defaultFont},deleteFontClicked:function(a){c.confirm("Delete font "+a+"?",function(){d.post("/api/facebook/delete-font/",{font:a},{success:function(a){console.dir(a.payload),b.updateFontsFromPayload(a.payload)}})})},setDefaultFontClicked:function(a){d.post("/api/facebook/set-default-font/",{font:a},{success:function(a){console.dir(a.payload),b.updateFontsFromPayload(a.payload)}})},onDefaultImageChange:function(){d.post("/api/facebook/set-default-images/",{logo:b.defaultLogo,background:b.defaultBackground},{success:function(a){}})},onUploadSuccess:function(a){b.updateFontsFromPayload(a.data.payload)},customizeTemplateClicked:function(a){b.temporaryTemplate=angular.extend({},a),b.currentTemplate=a},updateTemplateClicked:function(){a.updateObject(b.currentTemplate,b.temporaryTemplate),b.currentTemplate=null,b.temporaryTemplate=b.defaultSiteThumbnail},cancelTemplateUpdateClicked:function(){a.updateObject(b.currentTemplate,b.temporaryTemplate),b.currentTemplate=null,b.temporaryTemplate=b.defaultSiteThumbnail}})}]}}]);