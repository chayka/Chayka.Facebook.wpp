'use strict';

angular.module('chayka-facebook-thumbnail-post', ['chayka-forms', 'chayka-nls', 'chayka-wp-admin', 'chayka-utils', 'chayka-facebook-thumbnail-generator'])
    .directive('postThumbnailEditor', ['utils', function(utils) {
        return {
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-post.html'),
            scope: {
                fonts: '=?',
                defaultFont: '@?',
                defaultLogo: '@?',
                defaultBackground: '@?',
                templates: '=?',
                customTemplate: '=?',
                layout: '=?'
            },
            controller: ['$scope', 'modals', 'ajax', function ($scope, modals, ajax) {
                var tg = window.Chayka.Facebook.ThumbnailGenerator;

                angular.extend($scope, tg);

                angular.extend($scope, {
                    isGeneratorSetUp: function(){
                        return !!$scope.fonts && $scope.defaultFont && $scope.templates;
                    },

                    getLayoutOptions: function(){
                        var options = {
                            'featured': 'Featured Image'
                        };

                        for(var id in $scope.templates){
                            if($scope.templates.hasOwnProperty(id)){
                                options[id] = $scope.templates[id].name;
                            }
                        }

                        if($scope.customTemplate){
                            options.custom = 'Custom Layout';
                        }

                        return options;
                    }
                });

            }]
        };
    }])
;