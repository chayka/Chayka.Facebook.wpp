<?php

namespace Chayka\Facebook;

use Chayka\Helpers\InputHelper;
use Chayka\WP\Models\PostModel;
use Chayka\WP\MVC\Controller;

class MetaboxController extends Controller{

    public function init(){
        global $post;

        $action = InputHelper::getParam('action');
        wp_nonce_field($action, $action.'_nonce' );

        $richPost = PostModel::unpackDbRecord($post);

        $this->view->assign('post', $richPost);

        $this->enqueueStyle('chayka-wp-admin');
    }

    public function facebookOpenGraphAction(){
        $this->enqueueNgScriptStyle('chayka-facebook-thumbnail-generator');

        FontHelper::init();

        $url = FontHelper::createCssFile('fonts.css');

        wp_enqueue_style('facebook-gd2-fonts', $url);

        $this->view->assign('fonts', FontHelper::getTrueTypeFontNames());
    }
}