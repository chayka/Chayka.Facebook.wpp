<?php

namespace Chayka\Facebook;

use Chayka\WP\MVC\Controller;

class AdminController extends Controller{

    public function init(){
        $this->enqueueScript('chayka-options-form');
        $this->enqueueStyle('chayka-options-form');
    }

    public function facebookAction(){

    }
}