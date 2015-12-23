<?php

namespace Chayka\Facebook;

use Chayka\Helpers\FsHelper;
use Chayka\Helpers\JsonHelper;
use Chayka\Helpers\Util;
use Chayka\WP\Models\PostModel;
use Chayka\WP\Models\TermModel;
use Chayka\WP\Queries\TermQuery;

class ThumbnailHelper{
    const THUMBNAIL_WIDTH = 1200;
    const THUMBNAIL_HEIGHT = 630;

    protected static $defaultFont = '';
    protected static $defaultLogo = '';
    protected static $defaultBackground = '';

    /**
     * Get saved templates
     *
     * @return mixed|void
     */
    public static function getTemplates(){
        return OptionHelper::getOption('thumbnailTemplates');
    }

    /**
     * Get unique hash for the thumbnail to render
     *
     * @param array $template
     * @param array $blocks
     *
     * @return string
     */
    public static function getThumbnailHash($template, $blocks){
        self::$defaultFont = OptionHelper::getOption('thumbnailDefaultFont');
        self::$defaultLogo = OptionHelper::getOption('thumbnailDefaultLogo');
        self::$defaultBackground = OptionHelper::getOption('thumbnailDefaultBackground');
        $unique = join("\n", [
            self::$defaultFont,
            self::$defaultLogo,
            self::$defaultBackground,
            JsonHelper::encode($template),
            JsonHelper::encode($blocks),
        ]);

        return sprintf('%x',crc32($unique));
    }

    /**
     * Get common site text blocks
     *
     * @return array
     */
    public static function getSiteTextBlocks(){
        return [
            'domain' => [
                'title' => 'Domain',
                'text' => Util::serverName()
            ],
            'site_title' => [
                'title' => 'Site Title',
                'text' => get_bloginfo('site_name')
            ],
            'site_description' => [
                'title' => 'Site Description',
                'text' => get_bloginfo('description')
            ],
        ];
    }

    /**
     * Get data to render site thumbnail
     *
     * @return array|bool
     */
    public static function getSiteThumbnailData(){
        $templates = OptionHelper::getOption('thumbnailTemplates', []);

        $template = Util::getItem($templates, 'site');

        if(!$template){
            return false;
        }

        $blocks = self::getSiteTextBlocks();

        return [
            'template' => $template,
            'blocks' => $blocks
        ];
    }

    /**
     * Render site thumbnail
     *
     * @return bool|resource
     */
    public static function renderSiteThumbnail(){
        $data = self::getSiteThumbnailData();
        return $data ? self::renderThumbnail($data['template'], $data['blocks']):false;
    }

    /**
     * Get site thumbnail url
     *
     * @return string
     */
    public static function getSiteThumbnailUrl(){
        $data = self::getSiteThumbnailData();
        return sprintf('%s://%s/api/facebook/site-thumbnail/%s/',
            Util::isHttps()?'https':'http',
            $_SERVER['SERVER_NAME'],
            $data?self::getThumbnailHash($data['template'], $data['blocks']):$_SERVER['SERVER_NAME']);
    }

    /**
     * Get post specific text blocks
     *
     * @param PostModel|'sample' $post
     *
     * @return array
     */
    public static function getPostTextBlocks($post){
        if('sample' === $post){
            $topCategories = TermQuery::query('category')
                                      ->order_DESC()
                                      ->orderBy_Count()
                                      ->number(3)
                                      ->fields_Names()
                                      ->select();

            if(empty($topCategories)){
                $topCategories = ['Articles', 'News', 'Reviews'];
            }

            $topTags = TermQuery::query('post_tag')
                                ->order_DESC()
                                ->orderBy_Count()
                                ->number(3)
                                ->fields_Names()
                                ->select();

            if(empty($topTags)){
                $topTags = ['life', 'hack', 'mastery'];
            }

            return [
                'title' => [
                    'title' => 'Title',
                    'text' => 'The quick brown fox jumps over the lazy dog',
                    'type' => 'post',
                ],
                'excerpt' => [
                    'title' => 'Excerpt',
                    'text' => substr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 0, 253).'...',
                    'type' => 'post',
                ],
                'categories' => [
                    'title' => 'Categories',
                    'text' => $topCategories,
                    'type' => 'post',
                ],
                'tags' => [
                    'title' => 'Tags',
                    'text' => $topTags,
                    'type' => 'post',
                ],
            ];

        }
        if($post instanceof PostModel){
            return [
                'title'      => [
                    'title' => 'Title',
                    'text'  => $post->getTitle()
                ],
                'excerpt'    => [
                    'title' => 'Excerpt',
                    'text'  => $post->getExcerpt()
                ],
                'categories' => [
                    'title' => 'Categories',
                    'text'  => $post->getTerms('category')
                ],
                'tags'       => [
                    'title' => 'Tags',
                    'text'  => $post->getTerms('post_tag')
                ],
            ];
        }

        return [];
    }

    /**
     * Get data to render post template
     *
     * @param PostModel $post
     * @param string $layout
     *
     * @return array|bool
     */
    public static function getPostThumbnailData($post, $layout=''){
        if(!$layout){
            $layout = $post->getMeta('fb_thumbnail_layout');
        }
        $templates = OptionHelper::getOption('thumbnailTemplates', []);
        $postTemplates = Util::getItem($templates, 'post', []);
        if(!$layout){
            $layout = key($postTemplates);
        }
        $template = null;
        switch($layout){
            case 'featured':
                return false;
            case 'custom':
                $template = $post->getMeta('fb_thumbnail_custom');
                if($template){
                    $template = json_decode($template, true);
                }
                break;
            default:
                $template = Util::getItem($postTemplates, $layout);
        }
        if(!$template){
            return false;
        }
        $tb = $post->getThumbnailData_Full();
        $template['defaultBackground'] = $tb ? $tb['url'] : OptionHelper::getOption('thumbnailDefaultBackground');

        $blocks = array_merge(self::getSiteTextBlocks(), self::getPostTextBlocks($post));

        return [
            'template' => $template,
            'blocks' => $blocks
        ];
    }

    /**
     * Render post specific thumbnail
     *
     * @param PostModel $post
     * @param $layout
     *
     * @return bool|resource
     */
    public static function renderPostThumbnail($post, $layout = ''){
        $data = self::getPostThumbnailData($post, $layout);
        return $data ? self::renderThumbnail($data['template'], $data['blocks']):false;
    }

    /**
     * Get post thumbnail url
     *
     * @param PostModel $post
     *
     * @return string
     */
    public static function getPostThumbnailUrl($post){
        $data = self::getPostThumbnailData($post);
        return sprintf('%s://%s/api/facebook/post-thumbnail/%d/%s',
            Util::isHttps()?'https':'http',
            $_SERVER['SERVER_NAME'],
            $post->getId(),
            $data?self::getThumbnailHash($data['template'], $data['blocks']):''
        );
//        return '/api/facebook/post-thumbnail/'.$post->getId().'.png';
    }

    /**
     * Get taxonomy term text block
     *
     * @param TermModel|'sample' $term
     *
     * @return array
     */
    public static function getTaxonomyTextBlocks($term){
        if('sample' === $term){
            $topCategories = TermQuery::query('category')
                                      ->order_DESC()
                                      ->orderBy_Count()
                                      ->number(3)
                                      ->fields_Names()
                                      ->select();

            if(empty($topCategories)){
                $topCategories = ['Articles', 'News', 'Reviews'];
            }

            return [
                'taxonomy' => [
                    'title' => 'Taxonomy',
                    'text' => reset($topCategories),
                    'type' => 'taxonomy',
                ],
            ];

        }
        if($term instanceof TermModel){
            return [
                'taxonomy' => [
                    'title' => 'Taxonomy',
                    'text' => $term->getName(),
                    'type' => 'taxonomy',
                ],
            ];
        }
        return [];
    }

    /**
     * Get data to render taxonomy thumbnail
     *
     * @param TermModel $term
     * @param string $layout
     *
     * @return array|bool
     */
    public static function getTaxonomyThumbnailData($term, $layout = ''){
        $templates = OptionHelper::getOption('thumbnailTemplates', []);

        $taxonomyTemplates = Util::getItem($templates, 'taxonomy');
        if(!$layout){
            $layout = key($taxonomyTemplates);
        }
        $template = Util::getItem($taxonomyTemplates, $layout);
        if(!$template){
            return false;
        }
        $template['defaultBackground'] = OptionHelper::getOption('thumbnailDefaultBackground');

        $blocks = array_merge(self::getSiteTextBlocks(), self::getTaxonomyTextBlocks($term));

        return [
            'template' => $template,
            'blocks' => $blocks
        ];
    }

    /**
     * @param TermModel $term
     * @param string $layout
     *
     * @return bool|resource
     */
    public static function renderTaxonomyThumbnail($term, $layout = ''){
        $data = self::getTaxonomyThumbnailData($term, $layout);
        return $data?self::renderThumbnail($data['template'], $data['blocks']):false;
    }

    /**
     * Get taxonomy thumbnail url
     *
     * @param TermModel $term
     *
     * @return string
     */
    public static function getTaxonomyThumbnailUrl($term){
        $data = self::getTaxonomyThumbnailData($term);
        return sprintf('%s://%s/api/facebook/taxonomy-thumbnail/%s/%s/%s',
            Util::isHttps()?'https':'http',
            $_SERVER['SERVER_NAME'],
            $term->getTaxonomy(),
            $term->getSlug(),
            $data?self::getThumbnailHash($data['template'], $data['blocks']):'');
//        return '/api/facebook/taxonomy-thumbnail/'.$term->getTaxonomy().'/'.$term->getSlug().'.png';
    }

    /**
     * Render facebook thumbnail
     *
     * @param $template
     * @param $blocks
     *
     * @return bool|resource
     */
    public static function renderThumbnail($template, $blocks){
        FontHelper::init();

        $thumbnailWidth = self::THUMBNAIL_WIDTH;
        $thumbnailHeight = self::THUMBNAIL_HEIGHT;

        self::$defaultFont = OptionHelper::getOption('thumbnailDefaultFont');
        self::$defaultLogo = OptionHelper::getOption('thumbnailDefaultLogo');
        self::$defaultBackground = OptionHelper::getOption('thumbnailDefaultBackground');
        if(isset($template['defaultBackground'])){
            self::$defaultBackground = $template['defaultBackground'];
        }

        $bgUrl = $template['background']['imageMode'] === 'custom' ?
            $template['background']['url'] :
            self::$defaultBackground;
        $bgPath = self::url2path($bgUrl);

        $bg = self::readImage($bgPath);

        $im = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

        imagealphablending ($im, true);
        imagesavealpha ( $im, false );

        $bgWidth = imagesx($bg);
        $bgHeight = imagesy($bg);
        $widthRatio = $thumbnailWidth / $bgWidth;
//        $heightRatio = $thumbnailHeight / $bgHeight;
        $rescaledBgHeight = $bgHeight * $widthRatio;
        $rescaledBgWidth = $thumbnailWidth * $bgHeight / $thumbnailHeight;

        if($rescaledBgHeight >= $thumbnailHeight){
            imagecopyresampled($im, $bg, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $bgWidth, $thumbnailHeight * $bgWidth / $thumbnailWidth);
        }else{
            imagecopyresampled($im, $bg, 0, 0, ($bgWidth - $rescaledBgWidth) / 2, 0, $thumbnailWidth, $thumbnailHeight, $rescaledBgWidth, $bgHeight);
        }
        $im = imagecrop($im, [
            'x' => round((imagesx($im) - $thumbnailWidth) / 2),
            'y' => 0,
            'width' => $thumbnailWidth,
            'height' => $thumbnailHeight
        ]);

        if($template['fade']){
            $template['fade'] = array_merge($template['fade'], [
                'x' => 0,
                'y' => 0,
                'width' => 100,
                'height' => 100,
                'unitWidth' => '%',
                'unitHeight' => '%'
            ]);

            self::renderBlock($im, $template['fade']);
        }

        $lgUrl = $template['logo']['imageMode'] === 'custom' ?
            $template['logo']['url'] :
            self::$defaultLogo;
        $lgPath = self::url2path($lgUrl);
//        $lgPath = $template['logo']?self::url2path($template['logo']['url']):'';
        $logo = $lgPath?self::readImage($lgPath):null;

        if($logo){
            imagealphablending ($logo, false);
            imagesavealpha ( $logo, true );
            $width = Util::getItem($template['logo'], 'width', 0);
            $unitWidth = Util::getItem($template['logo'], 'unitWidth', 'px');
            $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;
            $logoRatio = $width / imagesx($logo);
            $height = imagesy($logo) * $logoRatio;
            $template['logo']['height'] = $height / 2;
            list($x, $y) = self::getBlockXY($template['logo']);

            imagecopyresampled($im, $logo, $x, $y, 0, 0, $width, $height, imagesx($logo), imagesy($logo));
        }
//        var_dump($blocks);
//        var_dump($template);
//        die();
        foreach($blocks as $blockId =>$data){
            $block = Util::getItem($template, $blockId);
            if($block && !empty($block['active'])){
                self::renderTextBlock($im, $data['text'], $block);
            }
        }

        return $im;
    }

    /**
     * Convert url string to local filesystem path
     *
     * @param string $url
     *
     * @return string
     */
    protected static function url2path($url){
        return preg_replace('/^.*wp-content/', WP_CONTENT_DIR, $url);
    }

    /**
     * Read image and return gd resource
     *
     * @param string $filename
     *
     * @return null|resource
     */
    protected static function readImage($filename){
        $format = strtolower(FsHelper::getExtension($filename));

        switch($format){
            case 'jpg':
                $im = imagecreatefromjpeg($filename);
                break;
            case 'gif':
                $im = imagecreatefromgif($filename);
                break;
            case 'png':
                $im = imagecreatefrompng($filename);
                break;
            default:
                $im = null;
        }

        return $im;
    }

    /**
     * Parse css hashed color #RRGGBB to [$r, $g, $b]
     *
     * @param $hashColor
     *
     * @return mixed
     */
    protected static function hashColorToRGB($hashColor){
        if(preg_match('/^#([\d\w])([\d\w])([\d\w])$/', $hashColor, $m)){
            $hashColor = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
        }
        return sscanf(strtolower($hashColor), '#%02x%02x%02x');
    }

    /**
     * Allocate rgba color
     *
     * @param resource $image
     * @param string $hashColor
     * @param int $opacity
     *
     * @return int
     */
    protected static function allocateHashColor($image, $hashColor, $opacity = 100){
        list($r, $g, $b) = self::hashColorToRGB($hashColor);
        return imagecolorallocatealpha($image, $r, $g, $b, 127 - $opacity * 1.27);
    }

    /**
     * Get ttf string dimensions
     *
     * @param string $text
     * @param string $font
     * @param int $fontSize
     *
     * @return array
     */
    protected static function stringDimensions($text, $font, $fontSize){
        list($blx, $bly, $brx, $bry, $trx, $try, $tlx, $tly) = imagettfbbox($fontSize, 0, FontHelper::getFontFilePath($font), $text);
        return [
            'text' => $text,
            'width' => $trx - $tlx,
            'height' => $try - $bry,
            'tlx' => $tlx,
            'tly' => $tly,
            'trx' => $trx,
            'try' => $try,
            'blx' => $blx,
            'bly' => $bly,
            'brx' => $brx,
            'bry' => $bry,
        ];
    }

    /**
     * Split string into lines to fit certain box width
     *
     * @param string $text
     * @param string $fontFamily
     * @param int $fontSize
     * @param int $width
     *
     * @return array
     */
    protected static function splitStringToFitWidth($text, $fontFamily, $fontSize, $width){
        $words = preg_split('/\s+/imUs', $text);
        $currentLine = '';
        $currentData = $zeroData = self::stringDimensions($currentLine, $fontFamily, $fontSize);
        $lines = [];
        foreach($words as $word){
            $tryLine = trim($currentLine . ' ' . $word);
            $tryData = self::stringDimensions($tryLine, $fontFamily, $fontSize);
            if($tryData['width'] > $width){
                /**
                 * $tryLine does not fit $width
                 */
                $wordData = self::stringDimensions($word, $fontFamily, $fontSize);
                if($currentLine){
                    /**
                     * $word is not the only $word in this line,
                     * pushing word to the next line
                     */
                    $lines[] = $currentData;
                    $currentLine = $word;
                    $currentData = $wordData;
                }else{
                    /**
                     * $word is the only $word in this line,
                     * force push to current line
                     */
                    $lines[] = $wordData;
                    $currentLine = '';
                    $currentData = $zeroData;
                }
            }else{
                /**
                 * $tryLine does fit $width
                 */
                $currentLine = $tryLine;
                $currentData = $tryData;
            }
        }
        if($currentLine){
            $lines[] = $currentData;
        }

        return $lines;
    }

    /**
     * Get block start coordinates
     *
     * @param array $block
     *
     * @return array
     */
    protected static function getBlockXY($block){
        $x = Util::getItem($block, 'x', 0);
        $unitX = Util::getItem($block, 'unitX', 'px');
        $x = '%' === $unitX ? $x / 100 * self::THUMBNAIL_WIDTH : $x * 2;

        $y = Util::getItem($block, 'y', 0);
        $unitY = Util::getItem($block, 'unitY', 'px');
        $y = '%' === $unitY ? $y / 100 * self::THUMBNAIL_HEIGHT : $y * 2;

        $width = Util::getItem($block, 'width', 0);
        $unitWidth = Util::getItem($block, 'unitWidth', 'px');
        $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;

        $height = Util::getItem($block, 'height', 0);
        $unitHeight = Util::getItem($block, 'unitHeight', 'px');
        $height = '%' === $unitHeight ? $height / 100 * self::THUMBNAIL_HEIGHT : $height * 2;

        $anchor = Util::getItem($block, 'anchor', 'left-top');

        switch($anchor){
            case 'left-top':
                break;
            case 'center-top':
                $x -= $width / 2;
                break;
            case 'right-top':
                $x -= $width;
                break;
            case 'left-center':
                $y -= $height / 2;
                break;
            case 'center-center':
                $x -= $width / 2;
                $y -= $height / 2;
                break;
            case 'right-center':
                $x -= $width;
                $y -= $height / 2;
                break;
            case 'left-bottom':
                $y -= $height;
                break;
            case 'center-bottom':
                $x -= $width / 2;
                $y -= $height;
                break;
            case 'right-bottom':
                $x -= $width;
                $y -= $height;
                break;
        }

        return [round($x),round($y)];
    }

    /**
     * Render box for provided block data
     *
     * @param $image
     * @param array $params
     *
     * @return array
     */
    public static function renderBlock($image, $params = []){
        $width = Util::getItem($params, 'width', 300);
        $unitWidth = Util::getItem($params, 'unitWidth', 'px');
        $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;

        $height = Util::getItem($params, 'height', 160);
        $unitHeight = Util::getItem($params, 'unitHeight', 'px');
        $height = '%' === $unitHeight ? $height / 100 * self::THUMBNAIL_HEIGHT : $height * 2;

        $hashBgColor = Util::getItem($params, 'backgroundColor', '#000');
        $bgOpacity = Util::getItem($params, 'backgroundOpacity', 0);
        $bgColor = $hashBgColor ? self::allocateHashColor($image, $hashBgColor, $bgOpacity):null;

        $borderWidth = Util::getItem($params, 'borderWidth', 0) * 2;
        $borderWidthTop = $borderWidth < 0 ? Util::getItem($params, 'borderWidthTop', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthRight = $borderWidth < 0 ? Util::getItem($params, 'borderWidthRight', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthBottom = $borderWidth < 0 ? Util::getItem($params, 'borderWidthBottom', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthLeft = $borderWidth < 0 ? Util::getItem($params, 'borderWidthLeft', $borderWidth / 2) * 2 : $borderWidth;

        $hashBorderColor = Util::getItem($params, 'borderColor');
        $borderColor = $hashBorderColor ? self::allocateHashColor($image, $hashBorderColor):null;

        list($x, $y) = self::getBlockXY($params);

        if($bgColor){
            imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $bgColor);
        }
        if($borderColor){
            /**
             * Left border
             */
            for($i = 0; $i < $borderWidthLeft; $i++){
                imageline($image, $x + $i, $y, $x + $i, $y + $height, $borderColor);
            }

            /**
             * Top border
             */
            for($i = 0; $i < $borderWidthTop; $i++){
                imageline($image, $x, $y + $i, $x + $width, $y + $i, $borderColor);
            }

            /**
             * Right border
             */
            for($i = 0; $i < $borderWidthRight; $i++){
                imageline($image, $x + $width - $i, $y, $x + $width - $i, $y + $height, $borderColor);
            }

            /**
             * Bottom border
             */
            for($i = 0; $i < $borderWidthBottom; $i++){
                imageline($image, $x, $y + $height - $i, $x + $width, $y + $height - $i, $borderColor);
            }
        }

        return [$x, $y];
    }

    /**
     * Render text block for provided data
     *
     * @param $image
     * @param $text
     * @param array $params
     *
     * @return bool
     */
    protected static function renderTextBlock($image, $text, $params = []){

        if(is_array($text)){
            $text = join(', ', $text);
        }

        $textTransform = Util::getItem($params, 'textTransform', 'none');

        switch($textTransform){
            case 'uppercase':
                $text = mb_strtoupper($text);
                break;
            case 'lowercase':
                $text = mb_strtolower($text);
                break;
        }
        $fontSize = Util::getItem($params, 'fontSize', 10) * 1.5 ;
        $fontFamily = Util::getItem($params, 'fontFamily');
        if(!$fontFamily){
            return false;
        }
        if('default' === $fontFamily){
            $fontFamily = self::$defaultFont;
        }
        $width = Util::getItem($params, 'width', 600);
        $unitWidth = Util::getItem($params, 'unitWidth', 'px');
        $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;

        $textAlign = Util::getItem($params, 'textAlign', 'left');

        $color = self::allocateHashColor($image, Util::getItem($params, 'color', '#fff'));

        $padding = Util::getItem($params, 'padding', 0) * 2;
        $paddingTop = $padding < 0 ? Util::getItem($params, 'paddingTop', $padding / 2) * 2 : $padding;
        $paddingRight = $padding < 0 ? Util::getItem($params, 'paddingRight', $padding / 2) * 2 : $padding;
        $paddingBottom = $padding < 0 ? Util::getItem($params, 'paddingBottom', $padding / 2 ) * 2 : $padding;
        $paddingLeft = $padding < 0 ? Util::getItem($params, 'paddingLeft', $padding / 2) * 2 : $padding;

        $borderWidth = Util::getItem($params, 'borderWidth', 0) * 2;
        $borderWidthTop = $borderWidth < 0 ? Util::getItem($params, 'borderWidthTop', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthRight = $borderWidth < 0 ? Util::getItem($params, 'borderWidthRight', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthBottom = $borderWidth < 0 ? Util::getItem($params, 'borderWidthBottom', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthLeft = $borderWidth < 0 ? Util::getItem($params, 'borderWidthLeft', $borderWidth / 2) * 2 : $borderWidth;

        $clientWidth = $width - $borderWidthLeft - $paddingLeft - $paddingRight - $borderWidthRight;

        $lines = self::splitStringToFitWidth($text, $fontFamily, $fontSize, $clientWidth);

        $lineHeight = $fontSize * 1.5;
        $baseline = 0.25;

        $clientHeight = $lineHeight * count($lines);

        $height = $clientHeight + $borderWidthTop + $paddingTop + $paddingBottom + $borderWidthBottom;

        $params['height'] = $height / 2;

        list($x, $y) = self::renderBlock($image, $params);
        foreach($lines as $line){
            $offset = 0;
            switch($textAlign){
                case 'left':
                    $offset = 0;
                    break;
                case 'right':
                    $offset = $clientWidth - $line['width'];
                    break;
                case 'center':
                    $offset = intval(($clientWidth - $line['width'] ) / 2);
                    break;
            }
            imagettftext($image, $fontSize, 0,
                $x - $line['blx'] + $offset + $borderWidthLeft + $paddingLeft,
                $y + (1 - $baseline) * $lineHeight + $borderWidthTop + $paddingTop,
                $color, FontHelper::getFontFilePath($fontFamily), $line['text']);
//            imagefilledellipse($image, $x, $y, 3, 3, $dotColor);
//            imagefilledellipse($image, $x, $y + (1 - $baseline) * $lineHeight, 3, 3, $blColor);
            $y += $lineHeight;
        }

        return true;

    }

}