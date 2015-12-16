<?php

namespace Chayka\Facebook;

use Chayka\Helpers\CurlHelper;
use Chayka\Helpers\Util;
use Chayka\WP;
use Chayka\WP\Models\CommentModel;
use Chayka\WP\Models\UserModel;
use Facebook;


class Plugin extends WP\Plugin{

    /* chayka: constants */
    
    public static $instance = null;

    const FONTS_DIR = 'res/fonts';

    public static function init(){
        if(!static::$instance){
            static::$instance = $app = new self(__FILE__, array(
                'facebook'
                /* chayka: init-controllers */
            ));

            UserModel::addJsonMetaField('fb_user_id');
            CommentModel::addJsonMetaField('fb_user_id');

            $app->dbUpdate(array());
	        $app->addSupport_UriProcessing();
	        $app->addSupport_ConsolePages();
	        $app->addSupport_Metaboxes();


            /* chayka: init-addSupport */
        }
    }


    /**
     * Register your action hooks here using $this->addAction();
     */
    public function registerActions() {
        $this->addAction('wp_head', ['Chayka\\Facebook\\HtmlHelper', 'renderMeta']);
        $this->addAction('wp_head', ['Chayka\\Facebook\\HtmlHelper', 'renderJsInit']);
	    $this->addAction('wp_logout', function(){
		    $accessToken = Util::getItem($_SESSION, 'fb_access_token');
		    if($accessToken){
			    $fb = new Facebook\Facebook([
				    'app_id' => FacebookHelper::getAppID(),
				    'app_secret' => FacebookHelper::getAppSecret(),
				    'default_access_token' => $accessToken, // optional
			    ]);
			    $logoutUrl = $fb->getRedirectLoginHelper()->getLogoutUrl($accessToken, ($_SERVER['HTTPS']?'https://':'http://').$_SERVER['SERVER_NAME']);
			    CurlHelper::get($logoutUrl);
			    unset($_SESSION['fb_access_token']);
			    session_commit();
		    }
	    });
    	/* chayka: registerActions */
    }

    /**
     * Register your action hooks here using $this->addFilter();
     */
    public function registerFilters() {
        $this->addFilter('get_avatar', ['Chayka\\Facebook\\FacebookHelper', 'filterGetFbAvatar'], 10, 3);
        $this->addFilter('CommentModel.created', ['Chayka\\Facebook\\FacebookHelper', 'filterMarkCommentWithFbUserId']);
        $this->addFilter('pre_comment_approved', ['Chayka\\Facebook\\FacebookHelper', 'filterApproveFbUserComment'], 10, 2);
		/* chayka: registerFilters */
    }

    /**
     * Register scripts and styles here using $this->registerScript() and $this->registerStyle()
     *
     * @param bool $minimize
     */
    public function registerResources($minimize = false) {
        $this->registerBowerResources(true);
        $this->registerScript('angular-upload', 'lib/angular-upload/angular-upload.min.js', ['angular']);
        $this->registerStyle('angular-upload', 'lib/angular-upload/src/directives/btnUpload.min.css');

        $this->setResSrcDir('src/');
        $this->setResDistDir('dist/');

        $this->populateResUrl('facebook');

        $this->registerScript('chayka-facebook', 'ng/chayka-facebook.js', ['chayka-auth']);
        $this->registerNgScript('chayka-facebook-thumbnail-generator', 'ng/chayka-facebook-thumbnail-generator.js', ['chayka-forms', 'chayka-nls', 'chayka-utils', 'chayka-wp-admin']);
        $this->registerNgStyle('chayka-facebook-thumbnail-generator', 'ng/chayka-facebook-thumbnail-generator.css', ['chayka-forms', 'chayka-wp-admin']);
        $this->registerNgScript('chayka-facebook-thumbnail-setup', 'ng/chayka-facebook-thumbnail-setup.js', ['chayka-facebook-thumbnail-generator', 'angular-upload']);
        $this->registerNgStyle('chayka-facebook-thumbnail-setup', 'ng/chayka-facebook-thumbnail-setup.css', ['chayka-facebook-thumbnail-generator', 'angular-upload']);

		/* chayka: registerResources */
    }

    /**
     * Routes are to be added here via $this->addRoute();
     */
    public function registerRoutes() {
        $this->addRoute('default');
        $this->addRoute('thumbnail', 'facebook/thumbnail/:image_id', ['controller' => 'facebook', 'action'=>'thumbnail']);
    }

    /**
     * Registering console pages
     */
    public function registerConsolePages(){
        $this->addConsolePage('Facebook', 'update_core', 'facebook', '/admin/facebook', 'dashicons-facebook', '76');
        $this->addConsoleSubPage('facebook', 'Thumbnail Generator', 'update_core', 'thumbnail-generator', '/admin/thumbnail-generator');


        /* chayka: registerConsolePages */
    }
    
    /**
     * Add custom metaboxes here via addMetaBox() calls;
     */
    public function registerMetaBoxes(){
        $this->addMetaBox('facebook-open-graph', 'Facebook Open Graph', '/metabox/facebook-open-graph', 'normal', 'high', null);

        /* chayka: registerMetaBoxes */
    }

    /**
     * Remove registered metaboxes here via removeMetaBox() calls;
     */
    public function unregisterMetaBoxes(){
        /* chayka: unregisterMetaBoxes */
    }
}