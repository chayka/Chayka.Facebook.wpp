<?php

namespace Chayka\Facebook;

use Chayka\Helpers\FsHelper;
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

    /**
     * Facebook thumbnail
     */
    public function thumbnailAction(){
        $imageId = InputHelper::getParam('image_id');
        $imageFormat = FsHelper::getExtension($imageId);
        $postId = FsHelper::hideExtension($imageId);
        FontHelper::init('res/fonts', Plugin::getInstance());

        $thumbnailWidth = 1200;
        $thumbnailHeight = 630;

        $post = PostModel::selectById($postId);

        $json = $post->getMeta('fb_thumbnail');

        $data = json_decode($json, true);
//        Util::print_r($data);

        $bgPath = $this->url2path($data['background']);
        $lgPath = $this->url2path($data['logo']);

        $string = 'The quick brown fox jumps over the lazy dog';
//        $string = 'oooA';

//        $this->splitStringToWidth($string, 'Montserrat', 30, 1000);
//        die();
        try{
            $bg = $this->readImage($bgPath);

            $im = $bg ? $bg : imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
//            $im = $bg;
            imagealphablending ($im, true);
            imagesavealpha ( $im, false );

//            imagedestroy($bg);

            $faderColor = imagecolorallocatealpha($im, 0,0,0,50);

            imagefilledrectangle($im, 0, 0, imagesx($im), imagesy($im), $faderColor);

            $logo = $this->readImage($lgPath);

            if($logo){
                imagealphablending ($logo, false);
                imagesavealpha ( $logo, true );
                $logoWidth = 300;
                $bgRatio = imagesx($im) / $thumbnailWidth;
                $logoRatio = imagesx($logo) / $logoWidth;
                $dstLogoWidth = intval($logoWidth * $bgRatio);
                $dstLogoHeight = intval(imagesy($logo) / $logoRatio * $bgRatio);

                imagecopyresampled($im, $logo, 100 * $bgRatio, 100 * $bgRatio, 0, 0, $dstLogoWidth, $dstLogoHeight, imagesx($logo), imagesy($logo));
            }

            $im = imagescale($im, $thumbnailWidth);

//            $orange = imagecolorallocate($im, 220, 0, 200);
//            imagestring($im, 30, 10, 10, $string, $orange);
//            imagettftext($im, 30, 0, 50, 50, $orange, FontHelper::getFontFilePath('Montserrat'), $string);
            $this->renderTextBlock($im, $string, [
                'font-family' => 'ProximaNova_B',
                'font-size' => 40,
                'x' => 400,
                'y' => 150,
                'width' => 700,
//                'text-align' => 'right',
                'border-width' => 4,
                'border-color' => '#fff',
                'padding' => 40,
//                'color' => '#0f0',
                'background-color' => '#000000',
            ]);
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

    protected function renderTextBlock($image, $text, $params = []){
        $fontSize = Util::getItem($params, 'font-size', 10);
        $fontFamily = Util::getItem($params, 'font-family');
        $width = Util::getItem($params, 'width', 1000);
        $x = Util::getItem($params, 'x', 0);
        $y = Util::getItem($params, 'y', 100);
        $textAlign = Util::getItem($params, 'text-align', 'left');
//        $color = imagecolorallocate($image, 255, 255, 255);
        $color = $this->allocateHashColor($image, Util::getItem($params, 'color', '#fff'));

        $hashBgColor = Util::getItem($params, 'background-color');
        $bgColor = $hashBgColor ? $this->allocateHashColor($image, $hashBgColor):null;

        $padding = Util::getItem($params, 'padding', 0);
        $paddingTop = Util::getItem($params, 'padding-top', $padding);
        $paddingRight = Util::getItem($params, 'padding-right', $padding);
        $paddingBottom = Util::getItem($params, 'padding-bottom', $padding);
        $paddingLeft = Util::getItem($params, 'padding-left', $padding);

        $borderWidth = Util::getItem($params, 'border-width', 0);
        $borderWidthTop = Util::getItem($params, 'border-width-top', $borderWidth);
        $borderWidthRight = Util::getItem($params, 'border-width-right', $borderWidth);
        $borderWidthBottom = Util::getItem($params, 'border-width-bottom', $borderWidth);
        $borderWidthLeft = Util::getItem($params, 'border-width-left', $borderWidth);

        $hashBorderColor = Util::getItem($params, 'border-color');
        $borderColor = $hashBorderColor ? $this->allocateHashColor($image, $hashBorderColor):null;

        $hlColor = imagecolorallocate($image, 200, 200, 200);
        $dotColor = imagecolorallocate($image, 255, 0, 0);
        $blColor = imagecolorallocate($image, 0, 0, 255);

        $clientWidth = $width - $borderWidthLeft - $paddingLeft - $paddingRight - $borderWidthRight;

        $lines = $this->splitStringToFitWidth($text, $fontFamily, $fontSize, $clientWidth);

        $lineHeight = $fontSize * 1.5;
        $baseline = 0.25;

        $clientHeight = $lineHeight * count($lines);

        $height = $clientHeight + $borderWidthTop + $paddingTop + $paddingBottom + $borderWidthBottom;

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
            for($i = 0; $i < $borderWidthTop; $i++){
                imageline($image, $x, $y + $height - $i, $x + $width, $y + $height - $i, $borderColor);
            }
        }
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