<?php

namespace Chayka\Facebook;

use Chayka\Helpers;

class NlsHelper extends Helpers\NlsHelper {

    public static function getBaseDir(){
        return Plugin::getInstance()->getBasePath();
    }

} 