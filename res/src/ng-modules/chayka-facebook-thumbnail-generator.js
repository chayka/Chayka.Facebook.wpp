'use strict';

angular.module('chayka-facebook-thumbnail-generator', ['chayka-forms', 'chayka-nls', 'chayka-wp-admins'])
    .directive('facebookThumbnailGenerator', [function(){
        return {
            restrict: 'A',
            replace: true,
            template:
                '<div class="chayka-facebook-thumbnail_generator">' +
                '   <div class="preview">' +
                '   </div>' +
                '   <div class="editor">' +
                '       <div class="tabs">' +
                '           <div class="tab background">{{"Background" | nls}}</div>' +
                '       </div>' +
                '       <div class="forms">' +
                '           <div class="form background">' +
                '               <h1>{{"Background" | nls}}</h1>' +
                '           </div>' +
                '       </div>' +
                '   </div>' +
                '</div>',
            link: function($scope, elemtnt, attrs){

            }
        };
    }])
;