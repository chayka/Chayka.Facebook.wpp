<?php

namespace Chayka\Facebook;

use Chayka\WP\MVC\Controller;

class AdminController extends Controller{

    public function init(){
        $this->enqueueNgScriptStyle('chayka-options-form');
    }

    public function facebookAction(){

    }
}