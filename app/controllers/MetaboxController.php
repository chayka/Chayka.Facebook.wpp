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

    public function facebookAction(){

    }
}