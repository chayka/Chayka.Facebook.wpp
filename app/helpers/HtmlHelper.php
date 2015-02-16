<?php
/**
 * Created by PhpStorm.
 * User: borismossounov
 * Date: 15.02.15
 * Time: 15:57
 */

namespace Chayka\Facebook;


use Chayka\WP\Helpers\ResourceHelper;
use Chayka\WP\Models\PostModel;

class HtmlHelper extends \Chayka\WP\Helpers\HtmlHelper{

	/**
	 * Render view with supplied vars
	 *
	 * @param string $path
	 * @param array $vars
	 * @param bool $output
	 *
	 * @return string
	 */
	public static function renderView($path, $vars = array(), $output = true){
		$view = Plugin::getView();
		foreach($vars as $key=>$val){
			$view->assign($key, $val);
		}
		$res = $view->render($path);
		if($output){
			echo $res;
		}
		return $res;
	}

	/**
	 * Render JS SDK init
	 *
	 * @param string $locale
	 */
	public static function renderJsInit($locale = ''){
		if(FacebookHelper::isJsApiEnabled()) {
			self::renderView( 'facebook/js-init.phtml', array(
				'appId'  => FacebookHelper::getAppID(),
				'locale' => $locale ? $locale : NlsHelper::getLocale(),
			) );
			ResourceHelper::enqueueScript('chayka-facebook');
		}
	}

	/**
	 * Render head > meta for sweet FB sharing
	 */
	public static function renderMeta(){
		global $post;
		if(is_single() || is_page()){
			FacebookHelper::setType('article');
			FacebookHelper::setPost(PostModel::unpackDbRecord($post));
		}else{
			FacebookHelper::setType('website');
		}
		self::renderView('facebook/meta.phtml', FacebookHelper::getFbData());
	}

}