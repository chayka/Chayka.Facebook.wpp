<?php

namespace Chayka\Facebook;

use Chayka\WP\MVC\Controller;

class AdminController extends Controller{

    public function init(){
        $this->enqueueNgScriptStyle('chayka-options-form');
    }

    public function facebookAction(){

    }
    public function thumbnailGeneratorAction(){
        FontHelper::init();
        $url = FontHelper::createCssFile('fonts.css');
        wp_enqueue_style('facebook-gd2-fonts', $url);

        $this->enqueueNgScriptStyle('chayka-facebook-thumbnail-setup');
        $this->view->assign('fonts', FontHelper::getTrueTypeFontNames());
        $this->view->assign('defaultFont', OptionHelper::getOption('thumbnailDefaultFont'));
        $this->view->assign('defaultLogo', OptionHelper::getOption('thumbnailDefaultLogo'));
    }
}