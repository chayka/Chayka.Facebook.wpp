<?php

namespace Chayka\Facebook;

use Chayka\Helpers\FsHelper;
use Chayka\Helpers\HttpHeaderHelper;
use Chayka\Helpers\Util;
use Chayka\WP\Models\PostModel;
use Chayka\WP\Models\UserModel;
use Chayka\WP\MVC\Controller;
use Chayka\Helpers\InputHelper;
use Chayka\WP\Helpers\JsonHelper;
use Facebook;

class FacebookController extends Controller{

    public function init(){
        // NlsHelper::load('main');
        ini_set('memory_limit', '512M');
         InputHelper::captureInput();
    }

    /**
     * Authentication with FB user ID
     */
    public function loginAction() {
        $accessToken = InputHelper::getParam('accessToken');
        $userID = InputHelper::getParam('userID');

        $fb = new Facebook\Facebook([
            'app_id' => FacebookHelper::getAppID(),
            'app_secret' => FacebookHelper::getAppSecret(),
            'default_access_token' => $accessToken, // optional
        ]);

        try {
            // Get the Facebook\GraphNodes\GraphUser object for the current user.
            $response = $fb->get('/me');
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            JsonHelper::respondException($e);
            die();
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            JsonHelper::respondException($e);
            die();
        }

        $me = $response->getGraphUser();

        $user = null;
        if ($me && $me->getId() == $userID) {
            $email = $me->getProperty('email');
            if($email){
                /**
                 * FB provided FB user email, trying to find WP user with the same email
                 */
                $user = UserModel::selectByEmail($email);
                if($user){
                    /**
                     * There is WP user with such email, marking him with FB user ID
                     */
                    $user->updateMeta('fb_user_id', $userID);
                }
            }
            if(!$user){
                /**
                 * There are no user with such email or FB has not provided us with user email
                 * Trying to fetch by user ID
                 */
                $user = UserModel::query()
                     ->metaQuery('fb_user_id', $userID)
                     ->selectOne();
            }
            if (!$user) {
                /**
                 * No user found, creating new one
                 */
                $user = new UserModel();
                $wpUserId = $user->setLogin('fb' . $userID)
                                 ->setEmail($email?$email:$userID . "@facebook.com")
                                 ->setDisplayName($me->getName())
                                 ->setFirstName($me->getFirstName())
                                 ->setLastName($me->getLastName())
                                 ->setNicename(sanitize_title(Util::translit(strtolower(join('.', [$me->getFirstName(), $me->getLastName()])))))
                                 ->setPassword(wp_generate_password(12, false))
                                 ->insert();
                if ($wpUserId) {
                    $user->updateMeta('fb_user_id', $userID);
                    $user->updateMeta('source', 'facebook');
                    $user = UserModel::selectById($user->getId());
                }
            }

	        $_SESSION['fb_access_token'] = $accessToken;

            /**
             * Authenticating WP user
             */
            $secure_cookie = is_ssl();
            wp_set_auth_cookie($user->getId(), false, $secure_cookie);
            do_action('wp_login', $user->getLogin(), $user->getWpUser());
            JsonHelper::respond($user);
        }

        JsonHelper::respondError('', 'authentication_failed');

    }

    /**
     * Facebook channel action, possibly deprecated
     */
    public function channelAction(){
        $locale = InputHelper::getParam('locale', 'en_US');
        $cache_expire = 60*60*24*365;
        header("Pragma: public");
        header("Cache-Control: max-age=".$cache_expire);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
        die('<script src="//connect.facebook.net/'.$locale.'/all.js"></script>');
    }

    const THUMBNAIL_WIDTH = 1200;
    const THUMBNAIL_HEIGHT = 630;

    /**
     * Facebook thumbnail
     */
    public function thumbnailAction(){
        $imageId = InputHelper::getParam('image_id');
        $imageFormat = FsHelper::getExtension($imageId);
        $postId = FsHelper::hideExtension($imageId);
        FontHelper::init('res/fonts', Plugin::getInstance());

        $thumbnailWidth = self::THUMBNAIL_WIDTH;
        $thumbnailHeight = self::THUMBNAIL_HEIGHT;

        $post = PostModel::selectById($postId);

        $json = $post->getMeta('fb_thumbnail');

        if(!$json){
            if($post->getThumbnailId()){
                $tb = $post->getThumbnailData_Full();
                HttpHeaderHelper::redirect($tb['url']);
            }else{
                HttpHeaderHelper::setResponseCode(404);
                return $this->setNotFound404();
            }
        }
        $post->loadTerms();
        $blocks = [
            'site' => get_bloginfo('site_name'),
            'description' => get_bloginfo('description'),
            'title' => $post->getTitle(),
            'excerpt' => $post->getExcerpt(),
            'categories' => join(', ', $post->getTerms('category')),
            'tags' => join(', ', $post->getTerms('post_tag')),
        ];

        $data = json_decode($json, true);
//        Util::print_r($data);
//        Util::print_r($blocks);
//        die();
        $bgPath = $data['background']?$this->url2path($data['background']['url']):'';

        $string = 'The quick brown fox jumps over the lazy dog';
//        $string = 'oooA';

//        $this->splitStringToWidth($string, 'Montserrat', 30, 1000);
//        die();
        try{
            $bg = $this->readImage($bgPath);

            $im = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
//            $im = $bg;
            imagealphablending ($im, true);
            imagesavealpha ( $im, false );

//            imagedestroy($bg);

//            $fadeColor = imagecolorallocatealpha($im, 0,0,0,50);
//
//            imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $fadeColor);

            $bgWidth = imagesx($bg);
            $bgHeight = imagesy($bg);
            $widthRatio = $thumbnailWidth / $bgWidth;
            $heightRatio = $thumbnailHeight / $bgHeight;
            $rescaledBgHeight = $bgHeight * $widthRatio;
            $rescaledBgWidth = $bgWidth * $heightRatio;

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

            if($data['fade']){
                $data['fade'] = array_merge($data['fade'], [
                    'x' => 0,
                    'y' => 0,
                    'width' => 100,
                    'height' => 100,
                    'unitWidth' => '%',
                    'unitHeight' => '%'
                ]);

                $this->renderBlock($im, $data['fade']);
            }

            $lgPath = $data['logo']?$this->url2path($data['logo']['url']):'';
            $logo = $lgPath?$this->readImage($lgPath):null;

            if($logo){
                imagealphablending ($logo, false);
                imagesavealpha ( $logo, true );
                $width = Util::getItem($data['logo'], 'width', 0);
                $unitWidth = Util::getItem($data['logo'], 'unitWidth', 'px');
                $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;
                $logoRatio = $width / imagesx($logo);
                $height = imagesy($logo) * $logoRatio;
                $data['logo']['height'] = $height / 2;
                list($x, $y) = $this->getBlockXY($data['logo']);

                imagecopyresampled($im, $logo, $x, $y, 0, 0, $width, $height, imagesx($logo), imagesy($logo));
            }

            foreach($blocks as $blockId =>$text){
                $block = Util::getItem($data, $blockId);
                if($block && Util::getItem($block, 'active', true)){
                    $this->renderTextBlock($im, $text, $block);
                }
            }
//            die();
            switch($imageFormat){
                case 'jpg':
                    header("Content-type: image/jpg");
                    imagejpeg($im);
                    break;
                case 'gif':
                    header("Content-type: image/gif");
                    imagegif($im);
                    break;
                case 'png':
                default:
                    header("Content-type: image/png");
                    imagepng($im);
            }
            imagedestroy($im);
        }catch (\Exception $e){
            JsonHelper::respondException($e);
        }
    }

    protected function url2path($url){
        return preg_replace('/^.*wp-content/', WP_CONTENT_DIR, $url);
    }

    /**
     * Read image and return gd resource
     *
     * @param $filename
     *
     * @return null|resource
     */
    protected function readImage($filename){
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
    
    protected function hashColorToRGB($hashColor){
        if(preg_match('/^#([\d\w])([\d\w])([\d\w])$/', $hashColor, $m)){
            $hashColor = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
        }
        return sscanf(strtolower($hashColor), '#%02x%02x%02x');
    }

    protected function allocateHashColor($image, $hashColor, $opacity = 100){
        list($r, $g, $b) = $this->hashColorToRGB($hashColor);
//        Util::print_r($this->hashColorToRGB($hashColor));
        return imagecolorallocatealpha($image, $r, $g, $b, 127 - $opacity * 1.27);
    }

    protected function stringDimensions($text, $font, $fontSize){
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

    protected function splitStringToFitWidth($text, $fontFamily, $fontSize, $width){
        $words = preg_split('/\s+/imUs', $text);
        $currentLine = '';
        $currentData = $zeroData = $this->stringDimensions($currentLine, $fontFamily, $fontSize);
        $lines = [];
        foreach($words as $word){
            $tryLine = trim($currentLine . ' ' . $word);
            $tryData = $this->stringDimensions($tryLine, $fontFamily, $fontSize);
            if($tryData['width'] > $width){
                /**
                 * $tryLine does not fit $width
                 */
                $wordData = $this->stringDimensions($word, $fontFamily, $fontSize);
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

    protected function getBlockXY($block){
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

    public function renderBlock($image, $params = []){
        $width = Util::getItem($params, 'width', 300);
        $unitWidth = Util::getItem($params, 'unitWidth', 'px');
        $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;

        $height = Util::getItem($params, 'height', 160);
        $unitHeight = Util::getItem($params, 'unitHeight', 'px');
        $height = '%' === $unitHeight ? $height / 100 * self::THUMBNAIL_HEIGHT : $height * 2;

//        imagealphablending ($image, false);
//        imagesavealpha ( $image, true );

        $hashBgColor = Util::getItem($params, 'backgroundColor', '#000');
        $bgOpacity = Util::getItem($params, 'backgroundOpacity', 0);
        $bgColor = $hashBgColor ? $this->allocateHashColor($image, $hashBgColor, $bgOpacity):null;

        $borderWidth = Util::getItem($params, 'borderWidth', 0) * 2;
        $borderWidthTop = $borderWidth < 0 ? Util::getItem($params, 'borderWidthTop', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthRight = $borderWidth < 0 ? Util::getItem($params, 'borderWidthRight', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthBottom = $borderWidth < 0 ? Util::getItem($params, 'borderWidthBottom', $borderWidth / 2) * 2 : $borderWidth;
        $borderWidthLeft = $borderWidth < 0 ? Util::getItem($params, 'borderWidthLeft', $borderWidth / 2) * 2 : $borderWidth;

        $hashBorderColor = Util::getItem($params, 'borderColor');
        $borderColor = $hashBorderColor ? $this->allocateHashColor($image, $hashBorderColor):null;

        list($x, $y) = $this->getBlockXY($params);

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

    protected function renderTextBlock($image, $text, $params = []){
//        Util::print_r($params);
        $fontSize = Util::getItem($params, 'fontSize', 10) * 1.5 ;
        $fontFamily = Util::getItem($params, 'fontFamily');
        if(!$fontFamily){
            return false;
        }
        $width = Util::getItem($params, 'width', 600);
        $unitWidth = Util::getItem($params, 'unitWidth', 'px');
        $width = '%' === $unitWidth ? $width / 100 * self::THUMBNAIL_WIDTH : $width * 2;

        $textAlign = Util::getItem($params, 'textAlign', 'left');

        $color = $this->allocateHashColor($image, Util::getItem($params, 'color', '#fff'));

//        $hashBgColor = Util::getItem($params, 'backgroundColor', '#000');
//        $bgOpacity = Util::getItem($params, 'backgroundOpacity', 0);
//        $bgColor = $hashBgColor ? $this->allocateHashColor($image, $hashBgColor, $bgOpacity):null;

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

//        $hashBorderColor = Util::getItem($params, 'borderColor');
//        $borderColor = $hashBorderColor ? $this->allocateHashColor($image, $hashBorderColor):null;

        $clientWidth = $width - $borderWidthLeft - $paddingLeft - $paddingRight - $borderWidthRight;

        $lines = $this->splitStringToFitWidth($text, $fontFamily, $fontSize, $clientWidth);

        $lineHeight = $fontSize * 1.5;
        $baseline = 0.25;

        $clientHeight = $lineHeight * count($lines);

        $height = $clientHeight + $borderWidthTop + $paddingTop + $paddingBottom + $borderWidthBottom;

        $params['height'] = $height / 2;

        list($x, $y) = $this->renderBlock($image, $params);
//        list($x, $y) = $this->getBlockXY($params);

//        if($bgColor){
//            imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $bgColor);
//        }
//        if($borderColor){
//            /**
//             * Left border
//             */
//            for($i = 0; $i < $borderWidthLeft; $i++){
//                imageline($image, $x + $i, $y, $x + $i, $y + $height, $borderColor);
//            }
//
//            /**
//             * Top border
//             */
//            for($i = 0; $i < $borderWidthTop; $i++){
//                imageline($image, $x, $y + $i, $x + $width, $y + $i, $borderColor);
//            }
//
//            /**
//             * Right border
//             */
//            for($i = 0; $i < $borderWidthRight; $i++){
//                imageline($image, $x + $width - $i, $y, $x + $width - $i, $y + $height, $borderColor);
//            }
//
//            /**
//             * Bottom border
//             */
//            for($i = 0; $i < $borderWidthTop; $i++){
//                imageline($image, $x, $y + $height - $i, $x + $width, $y + $height - $i, $borderColor);
//            }
//        }
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

    }
}