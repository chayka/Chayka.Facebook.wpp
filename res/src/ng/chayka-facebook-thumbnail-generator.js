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
                fonts: '=?',
                postId: '@?'
            },

            controller: ['$scope', '$element', function($scope, $element){
                angular.extend($scope, {

                    blockControls:{

                    },

                    tab: 'background',

                    model: angular.extend({
                        background: {
                            url: ''
                        },
                        logo: {
                            url: '',
                            x: 0,
                            unitX: 'px',
                            y: 0,
                            unitY: 'px',
                            width: 100,
                            unitWidth: 'px'
                        },
                        fade: {
                            color: '#000000',
                            opacity: 50
                        }
                    }, $scope.model || {}),

                    init: function(){
                        //for(var block in $scope.blocks){
                        //    $s
                        //}
                    },

                    getThumbnailElement: function(block){
                        return $element.find('.thumbnail_preview .' + block);
                    },

                    getBlockStyle: function(block){
                        var model = $scope.model;
                        var style = model && model[block]?
                            {
                                'visibility': !!model[block].active?'visible':'hidden',
                                'font-family': model[block].font?model[block].font:'inherit',
                                'font-size': model[block].size?model[block].size + 'px':'1em',
                                'color': model[block].color?model[block].color:'#FFFFFF'
                            }:{
                                'visibility': 'hidden'
                            };

                        if($scope.blockControls[block]){
                            angular.extend(style, $scope.blockControls[block].getBlockStyle());
                        }

                        return style;
                    },

                    getLogoStyle: function(){
                        var style = {
                        };

                        if($scope.blockControls.logo){
                            angular.extend(style, $scope.blockControls.logo.getBlockStyle());
                        }

                        return style;
                    }


                });

                $scope.init();
            }],
            link: function($scope, element, attrs){

            }
        };
    }])
    .directive('facebookAnchorPicker', [function(){
        return {
            restrict: 'A',
            replace: true,
            template: '<div class="facebook-anchor_picker {{value}}" data-ng-click="pickAnchor($event)"></div>',
            scope: {
                value:'='
            },
            controller: ['$scope', '$element', function($scope, $element){
                angular.extend($scope, {

                    value: $scope.value || 'left-top',

                    pickAnchor: function($event){
                        var oneThirdX = $element.width() / 3,
                            oneThirdY = $element.height() / 3,
                            clickedThirdX = Math.ceil($event.offsetX / oneThirdX),
                            clickedThirdY = Math.ceil($event.offsetY / oneThirdY),
                            valueX = 'left',
                            valueY = 'top';

                        switch (clickedThirdX){
                            case 1:
                                valueX = 'left';
                                break;
                            case 2:
                                valueX = 'center';
                                break;
                            case 3:
                                valueX = 'right';
                        }

                        switch (clickedThirdY){
                            case 1:
                                valueY = 'top';
                                break;
                            case 2:
                                valueY = 'center';
                                break;
                            case 3:
                                valueY = 'bottom';
                        }

                        $scope.value = valueX + '-' + valueY;
                        console.dir({
                            'facebookAnchorPicker.pickAnchor':$event,
                            'el': $element,
                            'x': clickedThirdX,
                            'y': clickedThirdY,
                            'value': $scope.value
                        });

                    }
                });
            }]
        };
    }])
    .directive('facebookThumbnailBlockControl', [function(){
        return {
            restrict: 'A',
            replace: true,
            template:
                '<div class="facebook-thumbnail_block_control">' +
                '   <div class="block_field anchor">' +
                '       <label>{{"Anchor" | nls}}:</label>' +
                '       <select data-ng-model="model.anchor" data-ng-options="anchor as text for (anchor, text) in anchors" data-ng-change="convertBlockStyle()"></select>' +
                '       <div data-facebook-anchor-picker data-value="model.anchor"></div>' +
                '   </div>' +
                '   <div class="block_field size coord_x">' +
                '       <label>{{"Coord X" | nls}}:</label>' +
                '       <input type="number" data-ng-model="model.x" min="0">' +
                '       <select data-ng-model="model.unitX" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '   </div>' +
                '   <div class="block_field size coord_y">' +
                '       <label>{{"Coord Y" | nls}}:</label>' +
                '       <input type="number" data-ng-model="model.y" min="0">' +
                '       <select data-ng-model="model.unitY" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '   </div>' +
                '   <div class="block_field size width">' +
                '       <label>{{"Width" | nls}}:</label>' +
                '       <input type="number" data-ng-model="model.width" min="0">' +
                '       <select data-ng-model="model.unitWidth" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '   </div>' +
                '</div>',
            scope: {
                api: '=?facebookThumbnailBlockControl',
                model: '=',
                block: '@'
            },
            controller: ['$scope', function($scope){
                angular.extend($scope, {

                    model: angular.extend({
                        x: 0,
                        unitX: 'px',
                        y: 0,
                        unitY: 'px',
                        width: 50,
                        unitWidth: '%',
                        anchor: 'left-top'
                    }, $scope.model),

                    units: ['px', '%'],
                    anchors: {
                        'left-top': 'Top Left',
                        'center-top': 'Top Center',
                        'right-top': 'Top Right',
                        'left-center': 'Center Left',
                        'center-center': 'Center',
                        'right-center': 'Center Right',
                        'left-bottom': 'Bottom Left',
                        'center-bottom': 'Bottom Center',
                        'right-bottom': 'Bottom Right'
                    },

                    convertBlockStyle: function(){

                    },

                    getBlockStyle: function(){
                        var $block = $scope.$parent.getThumbnailElement($scope.block),
                            m = $scope.model,
                            //pos = $block.position(),
                            blockWidth = $block.width(),
                            blockHeight = $block.height(),
                            blockRatio = blockWidth / blockHeight,
                            canvasWidth = $block.parent().width(),
                            canvasHeight = $block.parent().height(),
                            top = '%' === m.unitY ? Math.round(m.y / 100 * canvasHeight) : m.y,
                            left = '%' === m.unitX ? Math.round(m.x / 100 * canvasWidth) : m.x,
                            width = '%' === m.unitWidth ? Math.round(m.width / 100 * canvasWidth) : m.width,
                            height = Math.round(width / blockRatio);

                        switch(m.anchor){
                            case 'left-top':
                                break;
                            case 'center-top':
                                left -= Math.floor(width / 2);
                                break;
                            case 'right-top':
                                left -= Math.floor(width);
                                break;
                            case 'left-center':
                                top -= Math.floor(height / 2);
                                break;
                            case 'center-center':
                                left -= Math.floor(width / 2);
                                top -= Math.floor(height / 2);
                                break;
                            case 'right-center':
                                left -= Math.floor(width);
                                top -= Math.floor(height / 2);
                                break;
                            case 'left-bottom':
                                top -= height;
                                break;
                            case 'center-bottom':
                                left -= Math.floor(width / 2);
                                top -= height;
                                break;
                            case 'right-bottom':
                                left -= Math.floor(width);
                                top -= Math.floor(height / 2);
                                break;
                        }

                        return {
                            top: top,
                            left: left,
                            width: width
                        };
                    },

                    renderBlock: function(){

                    }


                });

                $scope.api = {
                    getBlockStyle: $scope.getBlockStyle
                };
            }]
        };
    }])
;