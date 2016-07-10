<?php

namespace Chayka\Facebook;

use Chayka\Helpers\FsHelper;
use Chayka\Helpers\HttpHeaderHelper;
use Chayka\Helpers\Util;
use Chayka\WP\Helpers\AclHelper;
use Chayka\WP\Models\PostModel;
use Chayka\WP\Models\TermModel;
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

    /**
     * Upload fonts zip
     */
    public function uploadFontsZipAction(){
        AclHelper::apiPermissionRequired();
        if(!is_dir(Plugin::getInstance()->getBasePath().Plugin::FONTS_DIR)){
            mkdir(Plugin::getInstance()->getBasePath().Plugin::FONTS_DIR);
        }
        if(isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])){
            try{
                $zipFn = $_FILES['file']['tmp_name'];

                $za = new \ZipArchive();

                $za->open($zipFn);

                $entries = [];
                for( $i = 0; $i < $za->numFiles; $i++ ){
                    $entry = $za->getNameIndex( $i );
                    $ext = FsHelper::getExtension($entry);
                    if(in_array($ext, ['ttf', 'eot', 'otf', 'woff', 'svg'])){
                        copy('zip://'.$zipFn.'#'.$entry, Plugin::getInstance()->getBasePath().Plugin::FONTS_DIR.'/'.basename($entry));
                        $entries[] = $entry;
                    }
                }

                FontHelper::init();
                FontHelper::createCssFile('fonts.css');

                JsonHelper::respond($this->getFontsState());

            }catch(\Exception $e){
                JsonHelper::respondException($e);
            }
        }else{
            JsonHelper::respondError('File was not uploaded');
        }
    }

    /**
     * Delete font action
     */
    public function deleteFontAction(){
        AclHelper::apiPermissionRequired();
        $font = InputHelper::checkParam('font')->required()->getValue();
        InputHelper::validateInput(true);

        $files = glob(Plugin::getInstance()->getBasePath().Plugin::FONTS_DIR.'/'.$font.'.*');

        foreach($files as $file){
            unlink($file);
        }

        FontHelper::init();

        JsonHelper::respond($this->getFontsState());

    }

    /**
     * Set default font action
     */
    public function setDefaultFontAction(){
        AclHelper::apiPermissionRequired();
        $font = InputHelper::checkParam('font')->required()->getValue();
        InputHelper::validateInput(true);

        OptionHelper::setOption('thumbnailDefaultFont', $font);

        JsonHelper::respond($this->getFontsState());
    }


    /**
     * Get font state
     *
     * @return array
     */
    protected function getFontsState(){
        FontHelper::init();
        $fonts = FontHelper::getTrueTypeFontNames();
        $defaultFont = OptionHelper::getOption('thumbnailDefaultFont');
        if($defaultFont && !in_array($defaultFont, $fonts)){
            $defaultFont = '';
            OptionHelper::setOption('thumbnailDefaultFont', $defaultFont);
        }
        return [
            'fonts' => $fonts,
            'defaultFont' => $defaultFont
        ];
    }

    /**
     * Set default logo action
     */
    public function setDefaultImagesAction(){
        AclHelper::apiPermissionRequired();
        $logo = InputHelper::getParam('logo');
        $background = InputHelper::getParam('background');

        OptionHelper::setOption('thumbnailDefaultLogo', $logo);
        OptionHelper::setOption('thumbnailDefaultBackground', $background);

        JsonHelper::respond([
            'logo' => OptionHelper::getOption('thumbnailDefaultLogo'),
            'background' => OptionHelper::getOption('thumbnailDefaultBackground'),
        ]);
    }

    public function saveTemplatesAction(){
        $templates = InputHelper::checkParam('templates')->required()->getValue();
        InputHelper::validateInput(true);
        OptionHelper::setOption('thumbnailTemplates', $templates);

        JsonHelper::respond(OptionHelper::getOption('thumbnailTemplates'));
    }

    /**
     * Facebook site thumbnail
     */
    public function siteThumbnailAction(){
//        $hash = InputHelper::getParam('hash');
        $imageFormat = InputHelper::getParam('format', ThumbnailHelper::getDefaultImageFormat());

        try{

            $im = ThumbnailHelper::renderSiteThumbnail();

            if(!$im){
                return $this->setNotFound404();
            }

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
        return false;
    }

    /**
     * Facebook taxonomy thumbnail
     */
    public function taxonomyThumbnailAction(){
        $slug = InputHelper::getParam('term');
        $taxonomy = InputHelper::getParam('taxonomy');
        $imageFormat = InputHelper::getParam('format', ThumbnailHelper::getDefaultImageFormat());
        $slug = FsHelper::hideExtension($slug);

        $term = TermModel::selectBySlug($slug, $taxonomy);

        try{

            $im = ThumbnailHelper::renderTaxonomyThumbnail($term);

            if(!$im){
                $im = ThumbnailHelper::renderSiteThumbnail();
            }

            if(!$im){
                return $this->setNotFound404();
            }

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

        return false;
    }

    /**
     * Facebook post thumbnail
     */
    public function postThumbnailAction(){
        $imageId = InputHelper::getParam('image_id');
        $imageFormat = InputHelper::getParam('format', ThumbnailHelper::getDefaultImageFormat());
        $postId = FsHelper::hideExtension($imageId);

        $post = PostModel::selectById($postId);

        $layout = $post->getMeta('fb_thumbnail_layout');

        $tb = $post->getThumbnailData_Full();

        $templates = ThumbnailHelper::getTemplates();

        if(!$layout && $templates){
            $postTemplates = Util::getItem($templates, 'post', []);
            if($postTemplates){
                $layout = key($postTemplates);
            }
        }

        if(!$layout || 'featured' === $layout){
            if($tb){
                HttpHeaderHelper::redirect($tb['url']);
            }else{
                return $this->setNotFound404();
            }
        }

        try{

            $im = ThumbnailHelper::renderPostThumbnail($post, $layout);

            if(!$im){
                if($tb){
                    HttpHeaderHelper::redirect($tb['url']);
                }else{
                    $im = ThumbnailHelper::renderSiteThumbnail();
                }
            }

            if(!$im){
                return $this->setNotFound404();
            }

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
        return false;
    }


}