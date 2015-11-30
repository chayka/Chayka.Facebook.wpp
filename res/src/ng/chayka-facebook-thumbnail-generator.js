'use strict';

angular.module('chayka-facebook-thumbnail-generator', ['chayka-forms', 'chayka-nls', 'chayka-wp-admin', 'chayka-utils'])
    .directive('facebookThumbnailGenerator', ['utils', function(utils){
        return {
            restrict: 'A',
            replace: true,
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-generator.html'),
            scope: {
                model: '='
            },
            controller: ['$scope', function($scope){
                angular.extend($scope, {
                    tab: 'background'
                });
            }],
            link: function($scope, element, attrs){

            }
        };
    }])
;