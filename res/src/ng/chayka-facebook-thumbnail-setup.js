'use strict';

angular.module('chayka-facebook-thumbnail-setup', ['chayka-forms', 'chayka-nls', 'chayka-wp-admin', 'chayka-utils', 'chayka-facebook-thumbnail-generator', 'lr.upload'])
    .directive('thumbnailSetup', ['utils', function(utils){
        return {
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-setup.html'),
            scope:{
                fonts: '=',
                defaultFont: '@?',
                defaultLogo: '@?',
                defaultBackground: '@?'
            },
            controller: ['$scope', 'modals', 'ajax', function($scope, modals, ajax){

                var tg = window.Chayka.Facebook.ThumbnailGenerator;

                angular.extend($scope, tg);

                angular.extend($scope, {

                    tab: 'fonts',

                    currentTemplate: null,

                    temporaryTemplate: {},

                    activateTab: function(tab){
                        $scope.tab = tab;
                    },

                    isTabActive: function(tab){
                        if(!$scope.defaultFont){
                            return tab === 'fonts';
                        }
                        return tab === $scope.tab;
                    },

                    updateFontsFromPayload: function(payload){
                        $scope.fonts.splice(0, $scope.fonts.length);
                        var newFonts = payload.fonts;
                        newFonts.forEach(function(font){
                            $scope.fonts.push(font);
                        });
                        $scope.defaultFont = payload.defaultFont;
                    },

                    deleteFontClicked: function(font){
                        modals.confirm('Delete font '+font+'?', function(){
                            ajax.post('/api/facebook/delete-font/', {
                                font: font
                            }, {
                                success: function(data){
                                    console.dir(data.payload);
                                    $scope.updateFontsFromPayload(data.payload);
                                }
                            });
                        });
                    },

                    setDefaultFontClicked: function(font){
                        ajax.post('/api/facebook/set-default-font/', {
                            font: font
                        }, {
                            success: function(data){
                                console.dir(data.payload);
                                $scope.updateFontsFromPayload(data.payload);
                            }
                        });
                    },

                    onDefaultImageChange: function(){
                        ajax.post('/api/facebook/set-default-images/', {
                            logo: $scope.defaultLogo,
                            background: $scope.defaultBackground
                        }, {
                            success: function(data){
                                //console.dir(data.payload);
                                //$scope.updateFontsFromPayload(data.payload);
                            }
                        });
                    },

                    onUploadSuccess: function(response){
                        //console.dir({'upload': data});
                        $scope.updateFontsFromPayload(response.data.payload);
                    },

                    customizeTemplateClicked: function(template){
                        $scope.temporaryTemplate = angular.extend({}, template);
                        $scope.currentTemplate = template;
                    },

                    updateTemplateClicked: function(){
                        utils.updateObject($scope.currentTemplate, $scope.temporaryTemplate);
                        ajax.post('/api/facebook/save-templates',{
                            templates: $scope.templates
                        }, {
                            success: function(data){
                                $scope.currentTemplate = null;
                                $scope.temporaryTemplate = $scope.defaultSiteThumbnail;
                                utils.updateObject($scope.templates, data.payload);
                            }
                        });
                    },

                    cancelTemplateUpdateClicked: function(){
                        utils.updateObject($scope.currentTemplate, $scope.temporaryTemplate);
                        $scope.currentTemplate = null;
                        $scope.temporaryTemplate = $scope.defaultSiteThumbnail;
                    }

                });
            }]
        };
    }])
;