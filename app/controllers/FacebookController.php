<?php

namespace Chayka\Facebook;

use Chayka\WP\Models\UserModel;
use Chayka\WP\MVC\Controller;
use Chayka\Helpers\InputHelper;
use Chayka\WP\Helpers\JsonHelper;
use Facebook;

class FacebookController extends Controller{

    public function init(){
        // NlsHelper::load('main');
        // InputHelper::captureInput();
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
                                 ->setNicename(sanitize_title(strtolower(join('.', array($me->getFirstName(), $me->getLastName())))))
                                 ->setPassword(wp_generate_password(12, false))
                                 ->insert();
                if ($wpUserId) {
                    $user->updateMeta('fb_user_id', $userID);
                    $user->updateMeta('source', 'facebook');
                    $user = UserModel::selectById($user->getId());
                }
            }

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

    public function channelAction(){
        $locale = InputHelper::getParam('locale', 'en_US');
        $cache_expire = 60*60*24*365;
        header("Pragma: public");
        header("Cache-Control: max-age=".$cache_expire);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
        die('<script src="//connect.facebook.net/'.$locale.'/all.js"></script>');
    }
}