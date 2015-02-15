<?php

namespace Chayka\Facebook;

use Chayka\WP;
use Chayka\WP\Models\CommentModel;
use Chayka\WP\Models\UserModel;

class Plugin extends WP\Plugin{

    /* chayka: constants */
    
    public static $instance = null;

    public static function init(){
        if(!static::$instance){
            static::$instance = $app = new self(__FILE__, array(
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
        $this->addAction('wp_head', array('Chayka\\Facebook\\HtmlHelper', 'renderMeta'));
    	/* chayka: registerActions */
    }

    /**
     * Register your action hooks here using $this->addFilter();
     */
    public function registerFilters() {
		/* chayka: registerFilters */
    }

    /**
     * Register scripts and styles here using $this->registerScript() and $this->registerStyle()
     *
     * @param bool $minimize
     */
    public function registerResources($minimize = false) {
        $this->registerBowerResources(true);

        $this->setResSrcDir('src/');
        $this->setResDistDir('dist/');

		/* chayka: registerResources */
    }

    /**
     * Routes are to be added here via $this->addRoute();
     */
    public function registerRoutes() {
        $this->addRoute('default');
    }

    /**
     * Registering console pages
     */
    public function registerConsolePages(){
        $this->addConsolePage('Facebook', 'update_core', 'facebook', '/admin/facebook', 'dashicons-facebook', '75');

        /* chayka: registerConsolePages */
    }
    
    /**
     * Add custom metaboxes here via addMetaBox() calls;
     */
    public function registerMetaBoxes(){
        $this->addMetaBox('facebook', 'Facebook', '/metabox/facebook', 'normal', 'high', null);

        /* chayka: registerMetaBoxes */
    }

    /**
     * Remove registered metaboxes here via removeMetaBox() calls;
     */
    public function unregisterMetaBoxes(){
        /* chayka: unregisterMetaBoxes */
    }
}