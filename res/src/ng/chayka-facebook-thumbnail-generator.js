'use strict';

angular.module('chayka-facebook-thumbnail-generator', ['chayka-forms', 'chayka-nls', 'chayka-wp-admin', 'chayka-utils'])
    .directive('facebookThumbnailGenerator', ['utils', function(utils){
        return {
            restrict: 'A',
            replace: true,
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-generator.html'),
            scope: {
                model: '=',
                blocks: '=?',
                fonts: '=?'
            },
            controller: ['$scope', function($scope){
                angular.extend($scope, {

                    tab: 'background',



                    getBlockStyle: function(block){
                        var model = $scope.model;
                        return model && model[block]?
                            {
                                'visibility': !!model[block].active?'visible':'hidden',
                                'font-family': model[block].font?model[block].font:'inherit',
                                'font-size': model[block].size?model[block].size + 'px':'1em',
                                'color': model[block].color?model[block].color:'#FFFFFF'
                            }:{
                                'visibility': 'hidden'
                            };
                    }
                });
            }],
            link: function($scope, element, attrs){

            }
        };
    }])
;