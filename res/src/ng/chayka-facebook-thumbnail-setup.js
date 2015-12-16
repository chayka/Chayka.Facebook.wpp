'use strict';

angular.module('chayka-facebook-thumbnail-setup', ['chayka-forms', 'chayka-nls', 'chayka-wp-admin', 'chayka-utils', 'chayka-facebook-thumbnail-generator', 'lr.upload'])
    .directive('thumbnailSetup', ['utils', function(utils){
        return {
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-setup.html'),
            scope:{
                fonts: '=',
                defaultFont: '@',
                defaultLogo: '@'
            },
            controller: ['$scope', 'modals', 'ajax', function($scope, modals, ajax){
                angular.extend($scope, {

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

                    onUploadSuccess: function(response){
                        //console.dir({'upload': data});
                        $scope.updateFontsFromPayload(response.data.payload);
                    }
                });
            }]
        };
    }])
;