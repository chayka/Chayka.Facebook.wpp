<?php
/**
 * Created by PhpStorm.
 * User: borismossounov
 * Date: 01.12.15
 * Time: 14:25
 */

namespace Chayka\Facebook;


use Chayka\Helpers\FsHelper;
use Chayka\Helpers\Util;

class FontHelper{

    /**
     * Font directory path
     *
     * @var string
     */
    protected static $fontDirPath = '';

    /**
     * Font directory url
     *
     * @var string
     */
    protected static $fontDirUrl = '';

    /**
     * Extension to font format relations
     *
     * @var array
     */
    protected static $formats = [
        'ttf' => 'truetype',
        'eot' => 'embedded-opentype',
        'woff' => 'woff',
        'woff2' => 'woff2',
        'svg' => 'svg',
    ];

    /**
     * Discovered font data
     *
     * @var array
     */
    protected static $fonts = [];

    /**
     * Scan provided folder for fonts
     *
     * @param string $fontDir
     * @param Plugin $app
     */
    public static function init($fontDir, $app){
        static::$fontDirPath = $app->getInstance()->getBasePath().$fontDir;
        static::$fontDirUrl = $app->getInstance()->getBaseUrl().$fontDir;
        $files = FsHelper::readDir(static::$fontDirPath, false);

        foreach($files as $file){
            $ext = strtolower(FsHelper::getExtension($file));
            $name = preg_replace('/\.' . $ext . '$/is', '', $file);
            if(isset(self::$formats[$ext])){
                static::$fonts[$name][$ext] = $file;
            }
        }
    }

    /**
     * Return all found font data
     *
     * @return array
     */
    public static function getFonts(){
        return static::$fonts;
    }

    public static function getTrueTypeFontNames(){
        $res = [];
        foreach(static::$fonts as $font => $files){
            if(isset($files['ttf'])){
                $res[]=$font;
            }
        }
        return $res;
    }

    /**
     * Get css code snippet for specified font name
     *
     * @param string $fontName
     *
     * @return string
     */
    public static function getStyle($fontName){
        if(isset(static::$fonts[$fontName])){
            $srcArr = [];
            foreach(static::$fonts[ $fontName ] as $ext => $file){
                $format   = self::$formats[ $ext ];
                $srcArr[] = "url('$file') format('$format')";
            }
            $src = join(', ', $srcArr);

            $str = "/* $fontName */\n" .
                   "@font-face {\n" .
                   "\tfont-family: '$fontName';\n" .
                   "\tsrc: $src;\n" .
                   "\tfont-weight: normal;\n" .
                   "\tfont-style: normal;\n" .
                   "}\n\n";

            return $src ? $str : '';
        }

        return '';
    }

    public static function getFontFilePath($fontName, $format = 'ttf'){
        $font = Util::getItem(static::$fonts, $fontName, []);
        $file = Util::getItem($font, $format, null);
        return $file? static::$fontDirPath . '/' . $file : null;
    }

    /**
     * Generates css file and provides it's url
     *
     * @param $filename
     * @param null $font
     *
     * @return null|string
     */
    public static function createCssFile($filename, $font = null){
        $fonts = array_keys(static::$fonts);
        if($font){
            if(is_array($font)){
                /**
                 * Font array provided
                 */
                $fonts = $font;
            }else{
                /**
                 * Font name provided
                 */
                $fonts = [$font];
            }
        }

        $css = '';
        foreach($fonts as $name){
            $css .= self::getStyle($name);
        }

        if($css){
            $filenamePath = static::$fontDirPath . '/' . $filename;
            $filenameUrl = static::$fontDirUrl . '/' . $filename;

            FsHelper::saveFile($filenamePath, $css);

            return $filenameUrl;
        }

        return null;
    }
}