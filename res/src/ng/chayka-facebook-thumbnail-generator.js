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
                defaultFont: '=?',
                defaultBackground: '=?',
                defaultLogo: '=?',
                postId: '@?',
                mode: '=?',
                thumbnailWidth: '=?',
                thumbnailHeight: '=?'
            },

            controller: ['$scope', '$element', '$timeout', function($scope, $element, $timeout){
                angular.extend($scope, {

                    blockControls:{

                    },

                    tab: 'background',

                    modalTabPicker: null,

                    thumbnailWidth: $scope.thumbnailWidth || 600,
                    thumbnailHeight: $scope.thumbnailHeight || 315,

                    init: function(){
                        $scope.initModel();
                        $scope.$watch('model', function(){
                            $scope.initModel();
                            $timeout(function(){
                                $scope.$apply();
                            }, 0);
                        });
                    },

                    initModel: function(){
                        $scope.model = utils.setObjectDefaults($scope.model, {
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
                                backgroundColor: '#000000',
                                backgroundOpacity: 50
                            }
                        });

                        for(var block in $scope.blocks){
                            if($scope.blocks.hasOwnProperty(block)){
                                $scope.model[block] = $scope.model[block] || {};
                            }
                        }
                    },

                    getThumbnailElement: function(block){
                        return $element.find('.thumbnail_preview .' + block);
                    },

                    getModelImageUrl: function(model, defaultUrl){
                        return 'default' === model.imageMode ? defaultUrl : model.url;
                    },

                    getBlockStyle: function(block){
                        var style = $scope.blockControls[block] && $scope.blockControls[block].getBlockStyle() || {};
                        if('background' === block){
                            var url = $scope.getModelImageUrl($scope.model.background, $scope.defaultBackground);
                            style['background-image'] = url && 'url(' + url + ')';
                        }
                        return style;
                    },

                    getBlockText: function(block){
                        var text = $scope.blocks[block].text || '';
                        return Array.isArray(text) ? text.join(', ') : text;
                    },

                    getCanvasStyle: function(){
                        return {
                            height: $scope.model.background && $scope.model.background.url?
                                $element.find('.thumbnail_preview .preview_background').height() + 'px':
                                '315px'
                        };
                    },

                    activateTab: function(tab){
                        $scope.tab = tab;
                    },

                    isTabActive: function(tab){
                        return tab === $scope.tab;
                    },

                    isTabVisible: function(block){
                        var visible = $scope.model[block] && $scope.model[block].active;
                        if($scope.blocks[block].type && $scope.model.type){
                            return visible && $scope.blocks[block].type === $scope.model.type;
                        }
                        return visible;
                    },

                    addBlockClicked: function(id){
                        if(id){
                            var blockModel = {
                                x: 0,
                                unitX: 'px',
                                y: 0,
                                unitY: 'px',
                                width: 50,
                                unitWidth: '%',
                                anchor: 'left-top',

                                fontFamily: 'default',
                                fontSize: 20,
                                color: '#ffffff',
                                textAlign: 'left',

                                backgroundColor: '#000000',
                                backgroundOpacity: 0,
                                borderColor: '#ffffff',
                                borderWidth: 0,
                                borderWidthTop: 0,
                                borderWidthRight: 0,
                                borderWidthBottom: 0,
                                borderWidthLeft: 0,
                                padding: 0,
                                paddingTop: 0,
                                paddingRight: 0,
                                paddingBottom: 0,
                                paddingLeft: 0
                            };

                            $scope.model[id] = $scope.model[id] || blockModel;

                            $scope.model[id].active = true;

                        }else{
                            $scope.modalTabPicker.show();
                        }
                    },

                    getBlocksAvailable: function(){
                        var available = {};
                        for(var id in $scope.blocks){
                            if(
                                $scope.blocks.hasOwnProperty(id) &&
                                (!$scope.model[id] || !$scope.model[id].active) &&
                                (!$scope.blocks[id].type || !$scope.model.type || $scope.blocks[id].type === $scope.model.type)
                            ){
                                available[id] = $scope.blocks[id];
                            }
                        }

                        return available;
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
                    }
                });
            }]
        };
    }])
    .directive('facebookThumbnailBlockControl', ['utils', function(utils){
        return {
            restrict: 'A',
            replace: true,
            templateUrl: utils.getResourceUrl('facebook', 'ng/chayka-facebook-thumbnail-block-control.html'),
            scope: {
                api: '=?facebookThumbnailBlockControl',
                model: '=',
                block: '@',
                optional: '@?',
                title: '@',
                tabsStr: '@tabs',
                imageHint: '@?',
                text: '=?',
                fonts: '=?',
                defaultImage: '=?',
                defaultFont: '=?',
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
                        $scope.initModel();

                        $scope.$watch('model', $scope.initModel);
                    },

                    initModel: function(){
                        if($scope.isTabShown('position')) {
                            utils.setObjectDefaults($scope.model, {
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
                            utils.setObjectDefaults($scope.model, {
                                fontFamily: 'default',
                                fontSize: 20,
                                color: '#ffffff',
                                textAlign: 'left',
                                textTransform: 'none'
                            });
                        }
                        if($scope.isTabShown('box')) {
                            utils.setObjectDefaults($scope.model, {
                                backgroundColor: '#000000',
                                backgroundOpacity: 0,
                                borderColor: '#ffffff',
                                borderWidth: 0,
                                borderWidthTop: 0,
                                borderWidthRight: 0,
                                borderWidthBottom: 0,
                                borderWidthLeft: 0,
                                padding: 0,
                                paddingTop: 0,
                                paddingRight: 0,
                                paddingBottom: 0,
                                paddingLeft: 0
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

                    convertBlockStyle: function(){
                        var $block = $scope.$parent.getThumbnailElement($scope.block),
                            pos = $block.position(),
                            m = $scope.model,
                            left = pos.left,
                            unitX = utils.getItem(m, 'unitX', 'px'),
                            top = pos.top,
                            unitY = utils.getItem(m, 'unitY', 'px'),
                            width = $block.outerWidth(),
                            height = $block.outerHeight(),
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
                        //$scope.initModel();

                        var $block = $scope.$parent.getThumbnailElement($scope.block),
                            m = $scope.model,
                            style = {};

                        if(!m){
                            return {
                                'visibility':'hidden'
                            };
                        }

                        if($scope.isTabShown('text')){
                            var fontFamily = 'default' === m.fontFamily ? $scope.defaultFont : m.fontFamily;
                            angular.extend(style, {
                                'font-family': fontFamily || 'inherit',
                                'font-size': m.fontSize ? m.fontSize + 'px' : '1em',
                                'color': m.color || '#FFFFFF',
                                'text-align': m.textAlign,
                                'text-transform': m.textTransform
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
                                blockWidth = $block.outerWidth(),
                                blockHeight = $block.outerHeight(),
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
                            var borderWidth = m.borderWidth && m.borderWidth + 'px' || '0', borderWidths = [];
                            if(m.borderWidth < 0){
                                borderWidths.push((m.borderWidthTop && m.borderWidthTop + 'px' || 0) + '');
                                borderWidths.push((m.borderWidthRight && m.borderWidthRight + 'px' || 0) + '');
                                borderWidths.push((m.borderWidthBottom && m.borderWidthBottom + 'px' || 0) + '');
                                borderWidths.push((m.borderWidthLeft && m.borderWidthLeft + 'px' || 0) + '');
                                borderWidth = borderWidths.join(' ');
                            }
                            var padding = m.padding && m.padding + 'px' || '0', paddings = [];
                            if(m.borderWidth < 0){
                                paddings.push((m.paddingTop && m.paddingTop + 'px' || 0) + '');
                                paddings.push((m.paddingRight && m.paddingRight + 'px' || 0) + '');
                                paddings.push((m.paddingBottom && m.paddingBottom + 'px' || 0) + '');
                                paddings.push((m.paddingLeft && m.paddingLeft + 'px' || 0) + '');
                                padding = paddings.join(' ');
                            }
                            angular.extend(style, {
                                'background-color': $scope.hashColorAndOpacityToRGB(m.backgroundColor || '#000', m.backgroundOpacity || 0),
                                'border': 'solid ' + m.borderColor,
                                'border-width': borderWidth,
                                'padding': padding
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

                    switchComplexity: function(param){
                        var m = $scope.model;
                        if(m[param] >= 0){
                            m[param + 'Top'] = m[param];
                            m[param + 'Right'] = m[param];
                            m[param + 'Bottom'] = m[param];
                            m[param + 'Left'] = m[param];
                            m[param] = -1;
                        }else{
                            m[param] = Math.max(m[param + 'Top'], m[param + 'Right'], m[param + 'Bottom'], m[param + 'Left']);
                        }
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