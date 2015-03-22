<?php
/**
 * Created by PhpStorm.
 * User: borismossounov
 * Date: 15.02.15
 * Time: 13:58
 */

namespace Chayka\Facebook;

use Chayka\Helpers\Util;
use Chayka\WP\Models;
use Chayka\WP\Models\CommentModel;
use Chayka\WP\Models\UserModel;

class FacebookHelper {

	protected static $post = null;

	protected static $title = null;

	protected static $description = null;

	protected static $image = null;

	protected static $type = 'website';

	protected static $url = null;

	/**
	 * Set current Post
	 *
	 * @param Models\PostModel $post
	 */
	public static function setPost($post){
		self::$post = $post;
	}

	/**
	 * Get current Post
	 *
	 * @return Models\PostModel
	 */
	public static function getPost(){
		return self::$post;
	}

	/**
	 * Set current title
	 * @param string $title
	 */
	public static function setTitle($title){
		self::$title = $title;
	}

	/**
	 * Get current title by the following priority:
	 *      $post->getMeta('fb_title')
	 *      $post->getMeta('seo_title')
	 *      $post->getTitle()
	 *      self::$title
	 *      HtmlHelper::getHeadTitle()
	 *      OptionHelper::getOption('default_title', get_bloginfo( 'name' ))
	 *
	 *
	 * @param Models\PostModel $post
	 * @return String
	 */
	public static function getTitle($post = null){
		if(!$post){
			$post = self::$post;
		}
		if($post){
			if($post->getMeta('fb_title')){
				return $post->getMeta('fb_title');
			}
            if($post->getMeta('seo_title')){
                return $post->getMeta('seo_title');
            }
			return $post->getTitle();
		}
		if(self::$title){
			return self::$title;
		}
		if(HtmlHelper::getHeadTitle()){
			return HtmlHelper::getHeadTitle();
		}
		return OptionHelper::getOption('default_title', get_bloginfo( 'name' ));
	}

	/**
	 * Set current description
	 *
	 * @param string $description
	 */
	public static function setDescription($description){
		self::$description = $description;
	}

	/**
	 * Get current description by the following priority:
	 *      $post->getMeta('fb_description')
	 *      $post->getMeta('description')
	 *      $post->getMeta('seo_description')
	 *      $post->getExcerpt()
	 *      self::$description
	 *      HtmlHelper::getMetaDescription()
	 *      OptionHelper::getOption('default_description', get_bloginfo( 'description' ))
	 *
	 * @param Models\PostModel $post
	 * @return string
	 */
	public static function getDescription($post = null){
		if(!$post){
			$post = self::$post;
		}
		if($post){
			if($post->getMeta('fb_description')){
				return $post->getMeta('description');
			}
			if($post->getMeta('description')){
				return $post->getMeta('description');
			}
			if($post->getMeta('seo_description')){
				return $post->getMeta('seo_description');
			}
			return $post->getExcerpt();
		}
		if(self::$description){
			return self::$description;
		}
		if(HtmlHelper::getMetaDescription()){
			return HtmlHelper::getMetaDescription();
		}
		return OptionHelper::getOption('default_description', get_bloginfo( 'description' ));
	}

	public static function setImage($image){
		self::$image = $image;
	}

	/**
	 * Get post images:
	 *      thumbnail,
	 *      attached images
	 *      default image
	 *
	 * @param Models\PostModel $post
	 * @return String
	 */
	public static function getImages($post = null){
		$images = array();
		if(!$post){
			$post = self::$post;
		}
		if($post){
			$attachments = $post->getAttachments('image');
			$thumbId = $post->getThumbnailId();

			/**
			 * @var Models\PostModel $attachment
			 */
			if($thumbId && isset($attachments[$thumbId])){
				$attachment = $attachments[$thumbId];
				$data = $attachment->loadImageData('full');
				$images[]= Util::getItem($data, 'url');
				unset($attachments[$thumbId]);
			}
			foreach ($attachments as $attachment){
				$data = $attachment->loadImageData('full');
				$images[]= Util::getItem($data, 'url');
			}
		}
		if(self::$image){
			$images[]= self::$image;
		}
		$defImg = OptionHelper::getOption('default_image');
		if($defImg){
			$images[]= $defImg;
		}

		return array_unique($images);
	}

	/**
	 * Set post type
	 *
	 * @param string $type
	 */
	public static function setType($type){
		self::$type = $type;
	}

	/**
	 * Get post type
	 *
	 * @return string
	 */
	public static function getType(){
		return self::$type;
	}

	/**
	 * Set post url
	 *
	 * @param $url
	 */
	public static function setUrl($url){
		self::$url = $url;
	}

	/**
	 * Get post url
	 *
	 * @param Models\PostModel $post
	 * @return string
	 */
	public static function getUrl($post = null){
		if(!$post){
			$post = self::$post;
		}
		if($post){
			return $post->getHref();
//			return 'http://'.$_SERVER['SERVER_NAME'].$post->getHref();
		}
		if(self::$url){
			return self::$url;
		}
		return '//'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	}

	/**
	 * Get post author
	 *
	 * @param Models\PostModel $post
	 * @return string
	 */
	public static function getAuthor($post=null){
		if(!$post){
			$post = self::$post;
		}
		if($post){
			$user = Models\UserModel::selectById($post->getUserId());
			if($user && $user->getMeta('fb_user_id')){
				return $user->getMeta('fb_user_id');
			}
		}
		return '';
	}

	/**
	 * Get FB App ID setup in admin area
	 *
	 * @return string
	 */
	public static function getAppID(){
		return OptionHelper::getOption('app_id');
	}

	/**
	 * Get FB App Secret setup in admin area
	 *
	 * @return mixed|void
	 */
	public static function getAppSecret(){
		return OptionHelper::getOption('app_secret');
	}

	/**
	 * Get array of FB App admin ids
	 *
	 * @return array[string]
	 */
	public static function getAppAdmins(){
		return preg_split('/[\s,]+/', OptionHelper::getOption('admins'));
	}

	/**
	 * Check if need to enable JS API
	 * @return boolean
	 */
	public static function isJsApiEnabled(){
		return !!OptionHelper::getOption('init_js');
	}

	/**
	 * Check if need to check FB user status on page load
	 * @return boolean
	 */
	public static function isStatusChecked(){
		return !!OptionHelper::getOption('init_status');
	}

	/**
	 * Check if cookie is created by JS API
	 * @return bool
	 */
	public static function isCookieCreated(){
		return !!OptionHelper::getOption('init_cookie');
	}

	/**
	 * Check if widget rendering enabled
	 * @return bool
	 */
	public static function isWidgetsRenderEnabled(){
		return !!OptionHelper::getOption('init_xfbml');
	}

	/**
	 * Get all the data needed fo post sharing
	 *
	 * @param Models\PostModel $post
	 *
	 * @return array
	 */
	public static function getFbData($post = null){
		$data = array();
		$admins = self::getAppAdmins();
		if($admins){
			$data['admins']=$admins;
		}
		$appId = self::getAppID();
		if($appId){
			$data['app_id']=$appId;
		}
		$fbPost = self::getPost();
		$title = self::getTitle($post);
		if($title){
			$data['title']=$title;
		}
		$desc = self::getDescription($post);
		if($desc){
			$data['description']=$desc;
		}
		$url = self::getUrl($post);
		if($url){
			$data['url']= $url;
		}
		$images = self::getImages($post);
		if($images){
			$data['images']= $images;
		}
		$type = self::getType();
		if($type){
			$data['type']= $type;
		}
		$author = self::getAuthor($post);
		if($author){
			$data['author']= $author;
		}
		if($fbPost){
			$data['post']=$fbPost;
		}

		return $data;
	}

	/**
	 * Replace GrAvatar with FBAvatar
	 *
	 * @param $avatar
	 * @param $id_or_email
	 * @param int $size
	 *
	 * @return mixed
	 */
	public static function filterGetFbAvatar($avatar, $id_or_email, $size = 96){
		if(!$id_or_email){
			return $avatar;
		}
		$user = null;
		if(is_object($id_or_email)){
			$user = UserModel::unpackDbRecord($id_or_email);
		}else{
			$user = is_email($id_or_email)?
				UserModel::selectByEmail($id_or_email):
				UserModel::selectById($id_or_email);
		}
		if($user){
			$metaFbUseId = $user->getMeta('fb_user_id');
			if($metaFbUseId){
				if(!intval($size)){
					$size = 96;
				}
				$avatarUrl = sprintf('//graph.facebook.com/%s/picture?type=square&width=%d&height=%d', $metaFbUseId, (int)$size, $size);
				return preg_replace("%src='[^']*'%", "src='$avatarUrl'", $avatar);
			}
		}else{
//            return preg_replace("%alt='[^']*'%", "alt='user not found'", $avatar);
		}

		return $avatar;
	}

	/**
	 * Used to display comment FB avatar
	 *
	 * @param CommentModel $comment
	 * @return CommentModel
	 */
	public function filterMarkCommentWithFbUserId($comment){
		if($comment->getUserId()){
			$user = UserModel::selectById($comment->getUserId());
			if($user && $user->getMeta('fb_user_id')){
				$comment->updateMeta('fb_user_id', $user->getMeta('fb_user_id'));
			}
		}
		return $comment;
	}

	/**
	 * Used for instant comment approval
	 *
	 * @param $approved
	 * @param $rawComment
	 *
	 * @return bool
	 */
	public function filterApproveFbUserComment($approved, $rawComment){
		$userId = Util::getItem($rawComment, 'user_id');
		if(!$approved && $userId){
			$user = UserModel::selectById($userId);
			if($user && $user->getMeta('fb_user_id')){
				$approved = true;
			}
		}
		return $approved;
	}
}