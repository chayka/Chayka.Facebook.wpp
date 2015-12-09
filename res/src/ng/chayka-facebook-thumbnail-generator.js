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

                    init: function(){
                        utils.setDefaults($scope.model, {
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
                                unitWidth: 'px',
                                anchor: 'left-top'
                            },
                            fade: {
                                color: '#000000',
                                opacity: 50
                            }
                        });

                        for(var block in $scope.blocks){
                            if($scope.blocks.hasOwnProperty(block)){
                                $scope.model[block] = $scope.model[block] || {};
                                //utils.setDefaults($scope.model[], {
                                //    background: {
                                //        url: ''
                                //    },
                                //    logo: {
                                //        url: '',
                                //        x: 0,
                                //        unitX: 'px',
                                //        y: 0,
                                //        unitY: 'px',
                                //        width: 100,
                                //        unitWidth: 'px',
                                //        anchor: 'left-top'
                                //    },
                                //    fade: {
                                //        color: '#000000',
                                //        opacity: 50
                                //    }
                                //});

                            }
                        }
                    },

                    getThumbnailElement: function(block){
                        return $element.find('.thumbnail_preview .' + block);
                    },

                    getBlockStyle: function(block){
                        //var model = $scope.model;
                        //var style = model && model[block]?
                        //    {
                        //        'visibility': !!model[block].active?'visible':'hidden',
                        //        'font-family': model[block].font?model[block].font:'inherit',
                        //        'font-size': model[block].size?model[block].size + 'px':'1em',
                        //        'color': model[block].color?model[block].color:'#FFFFFF'
                        //    }:{
                        //        'visibility': 'hidden'
                        //    };
                        //
                        //if($scope.blockControls[block]){
                        //    angular.extend(style, $scope.blockControls[block].getBlockStyle());
                        //}
                        //
                        //return style;

                        return $scope.blockControls[block].getBlockStyle();
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
                value: '=',
                onChange: '&?'
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
                        var value = valueX + '-' + valueY,
                            valueChanged = value !== $scope.value;
                        $scope.value = value;
                        if($scope.onChange && valueChanged){
                            $scope.onChange();
                        }
                        //console.dir({
                        //    'facebookAnchorPicker.pickAnchor':$event,
                        //    'el': $element,
                        //    'x': clickedThirdX,
                        //    'y': clickedThirdY,
                        //    'value': $scope.value
                        //});

                    }
                });
            }]
        };
    }])
    .directive('facebookThumbnailBlockControl', ['utils', function(utils){
        return {
            restrict: 'A',
            replace: true,
            template:
                '<div class="facebook-thumbnail_block_control">' +
                '   <div class="block_title">{{title}}</div>' +
                '   <label class="checkbox is_active" data-ng-show="!!optional"><input type="checkbox" data-ng-model="model.active" title="Show block"/> enable</label>' +
                '   <div class="tabs" data-ng-show="tabs.length > 1">' +
                '       <div class="tab image" data-ng-show="isTabShown(\'image\')" data-ng-class="{active: \'image\' === tab}" data-ng-click="tab=\'image\'">Image</div>' +
                '       <div class="tab text" data-ng-show="isTabShown(\'text\')" data-ng-class="{active: \'text\' === tab}" data-ng-click="tab=\'text\'">Text</div>' +
                '       <div class="tab position" data-ng-show="isTabShown(\'position\')" data-ng-class="{active: \'position\' === tab}" data-ng-click="tab=\'position\'">Position</div>' +
                '       <div class="tab box" data-ng-show="isTabShown(\'box\')" data-ng-class="{active: \'box\' === tab}" data-ng-click="tab=\'box\'">Box</div>' +
                '   </div>' +
                '   <div class="form image" data-ng-show="\'image\' === tab">' +
                '       <div class="image_picker" data-media-picker data-mode="url" data-size="full" data-model="model.url" data-picker-button-text="Pick Image" data-title="Pick Image" >' +
                '           {{imageHint}}' +
                '       </div>' +
                '   </div>' +
                '   <div class="form position" data-ng-show="\'position\' === tab">' +
                '       <div class="block_field anchor">' +
                '           <label>{{"Anchor" | nls}}:</label>' +
                '           <select data-ng-model="model.anchor" data-ng-options="anchor as text for (anchor, text) in anchors" data-ng-change="convertBlockStyle()"></select>' +
                '           <div data-facebook-anchor-picker data-value="model.anchor" data-on-change="convertBlockStyle()"></div>' +
                '       </div>' +
                '       <div class="block_field size coord_x">' +
                '           <label>{{"Coord X" | nls}}:</label>' +
                '           <input type="number" data-ng-model="model.x" min="0">' +
                '           <select data-ng-model="model.unitX" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '       </div>' +
                '       <div class="block_field size coord_y">' +
                '           <label>{{"Coord Y" | nls}}:</label>' +
                '           <input type="number" data-ng-model="model.y" min="0">' +
                '           <select data-ng-model="model.unitY" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '       </div>' +
                '       <div class="block_field size width">' +
                '           <label>{{"Width" | nls}}:</label>' +
                '           <input type="number" data-ng-model="model.width" min="0">' +
                '           <select data-ng-model="model.unitWidth" data-ng-options="unit for unit in units" data-ng-change="convertBlockStyle()"></select>' +
                '       </div>' +
                '   </div>' +
                '   <div class="form text" data-ng-show="\'text\' === tab">' +
                '       <div class="block_field block_font_color color_picker" data-form-field="fontColor">' +
                '           <label class="width50">Font color</label>' +
                '           <input type="color" data-ng-model="model.color" data-default-color="#FFFFFF" data-color-picker title="Font color"/>' +
                '       </div>' +
                '       <div class="block_field font_size" data-form-field="fontSize">' +
                '           <label class="width50">Font size</label>' +
                '           <input type="number" data-ng-model="model.fontSize" title="Font size" min="0"/>' +
                '       </div>' +
                '       <div class="block_field font_family" data-form-field="fontFamily" data-ng-show="!!fonts.length">' +
                '           <label class="width50">Font family</label>' +
                '           <select data-ng-model="model.fontFamily" title="Font size" data-ng-options="font for font in fonts"></select>' +
                '       </div>' +
                '   </div>' +
                '   <div class="form box" data-ng-show="\'box\' === tab">' +
                '       <div class="block_field background_color color_picker">' +
                '           <label class="width50">Fade color</label>' +
                '           <input type="color" data-ng-model="model.backgroundColor" data-default-color="#000000" data-color-picker title="Background color"/>' +
                '       </div>' +
                '       <div class="block_field background_opacity">' +
                '           <label class="width50">Fade opacity</label>' +
                '           <input type="number" data-ng-model="model.backgroundOpacity" title="Background opacity" min="0" max="100"/>' +
                '       </div>' +
                '   </div>' +
                '</div>',
            scope: {
                api: '=?facebookThumbnailBlockControl',
                model: '=',
                block: '@',
                optional: '@?',
                title: '@',
                tabsStr: '@tabs',
                imageHint: '@?',
                text: '=?',
                fonts: '=?'
            },
            controller: ['$scope', function($scope){
                angular.extend($scope, {

                    tabs: $scope.tabsStr && $scope.tabsStr.split(/\s+/) || [],

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

                    init: function(){
                        $scope.tab = $scope.tabs[0];
                        if($scope.isTabShown('position')) {
                            utils.setDefaults($scope.model, {
                                x: 0,
                                unitX: 'px',
                                y: 0,
                                unitY: 'px',
                                width: 50,
                                unitWidth: '%',
                                anchor: 'left-top'
                            });
                        }
                        if($scope.isTabShown('text')) {
                            utils.setDefaults($scope.model, {
                                fontFamily: '',
                                fontSize: 20,
                                color: '#ffffff'
                            });
                        }
                        if($scope.isTabShown('box')) {
                            utils.setDefaults($scope.model, {
                                backgroundColor: '#000000',
                                backgroundOpacity: 0
                            });
                        }
                    },

                    isTabShown: function(tab){
                        return $scope.tabs.indexOf(tab) >= 0;
                    },

                    hashColorAndOpacityToRGB: function(hash, opacity){
                        hash = hash.replace('#','');
                        if(hash.length === 3){
                            hash = hash[0] + hash[0] + hash[1] + hash[1] + hash[2] + hash[2];
                        }
                        var r = parseInt(hash.substring(0,2), 16),
                            g = parseInt(hash.substring(2,4), 16),
                            b = parseInt(hash.substring(4,6), 16);

                        return 'rgba('+r+','+g+','+b+','+opacity/100+')';
                    },

                    convertBlockStyle: function($event){
                        var $block = $scope.$parent.getThumbnailElement($scope.block),
                            pos = $block.position(),
                            m = $scope.model,
                            left = pos.left,
                            unitX = utils.getItem(m, 'unitX', 'px'),
                            top = pos.top,
                            unitY = utils.getItem(m, 'unitY', 'px'),
                            width = $block.width(),
                            height = $block.height(),
                            unitW = utils.getItem(m, 'unitWidth', '%'),
                            anchor = utils.getItem(m, 'anchor', 'left-top'),
                            canvasWidth = $block.parent().width(),
                            canvasHeight = $block.parent().height();

                        switch(anchor){
                            default:
                            case 'left-top':
                                break;
                            case 'center-top':
                                left += Math.floor(width / 2);
                                break;
                            case 'right-top':
                                left += Math.floor(width);
                                break;
                            case 'left-center':
                                top += Math.floor(height / 2);
                                break;
                            case 'center-center':
                                left += Math.floor(width / 2);
                                top += Math.floor(height / 2);
                                break;
                            case 'right-center':
                                left += Math.floor(width);
                                top += Math.floor(height / 2);
                                break;
                            case 'left-bottom':
                                top += height;
                                break;
                            case 'center-bottom':
                                left += Math.floor(width / 2);
                                top += height;
                                break;
                            case 'right-bottom':
                                left += Math.floor(width);
                                top += Math.floor(height / 2);
                                break;
                        }
                        var y = '%' === unitY ? Math.round(top / canvasHeight * 100) : top,
                            x = '%' === unitX ? Math.round(left / canvasWidth * 100) : left,
                            w = '%' === unitW ? Math.round(width / canvasWidth * 100) : width;


                        angular.extend($scope.model, {
                            x: x,
                            y: y,
                            width: w
                        });
                    },

                    getBlockStyle: function(){
                        var $block = $scope.$parent.getThumbnailElement($scope.block),
                            m = $scope.model,
                            style = {};

                        if($scope.isTabShown('text')){
                            angular.extend(style, {
                                'font-family': m.fontFamily || 'inherit',
                                'font-size': m.fontSize ? m.fontSize + 'px' : '1em',
                                'color': m.color || '#FFFFFF'
                            });
                        }

                        if($scope.isTabShown('position')){
                            var x = utils.getItem(m, 'x', 0),
                                unitX = utils.getItem(m, 'unitX', 'px'),
                                y = utils.getItem(m, 'y', 0),
                                unitY = utils.getItem(m, 'unitY', 'px'),
                                w = utils.getItem(m, 'width', 50),
                                unitW = utils.getItem(m, 'unitWidth', '%'),
                                anchor = utils.getItem(m, 'anchor', 'left-top'),
                                blockWidth = $block.width(),
                                blockHeight = $block.height(),
                                blockRatio = blockWidth / blockHeight,
                                canvasWidth = $block.parent().width(),
                                canvasHeight = $block.parent().height(),
                                top = '%' === unitY ? Math.round(y / 100 * canvasHeight) : y,
                                left = '%' === unitX ? Math.round(x / 100 * canvasWidth) : x,
                                width = '%' === unitW ? Math.round(w / 100 * canvasWidth) : w,
                                height = Math.round(width / blockRatio);

                            switch(anchor){
                                default:
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

                            angular.extend(style, {
                                top: top,
                                left: left,
                                width: width
                            });
                        }

                        if($scope.isTabShown('box')) {
                            angular.extend(style, {
                                'background-color': $scope.hashColorAndOpacityToRGB(m.backgroundColor || '#000', m.backgroundOpacity || 0)
                            });
                        }

                        if($scope.optional){
                            style = !!m.active ?
                                angular.extend(style, {
                                    'visibility': 'visible'
                                }) : {
                                    'visibility':'hidden'
                                };
                        }

                        return style;
                    },

                    renderBlock: function(){

                    }


                });

                $scope.init();

                $scope.api = {
                    getBlockStyle: $scope.getBlockStyle
                };
            }]
        };
    }])
;