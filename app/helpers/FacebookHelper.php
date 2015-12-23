<?php

namespace Chayka\Facebook;

use Chayka\Helpers\Util;
use Chayka\WP\Models;
use Chayka\WP\Models\CommentModel;
use Chayka\WP\Models\PostModel;
use Chayka\WP\Models\TermModel;
use Chayka\WP\Models\UserModel;

class FacebookHelper {

    /**
     * @var PostModel
     */
    protected static $post = null;

    /**
     * @var TermModel
     */
	protected static $term = null;

    /**
     * @var string
     */
	protected static $title = null;

    /**
     * @var string
     */
	protected static $description = null;

    /**
     * @var array|string
     */
	protected static $image = null;

    /**
     * @var string
     */
	protected static $type = 'website';

    /**
     * @var string
     */
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
     * @return TermModel
     */
    public static function getTerm(){
        return self::$term;
    }

    /**
     * @param TermModel $term
     */
    public static function setTerm($term){
        self::$term = $term;
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
	 * @param PostModel|TermModel $obj
     *
     * @return String
	 */
	public static function getTitle($obj = null){
		if($obj){
            if($obj instanceof PostModel){
                if($obj->getMeta('fb_title')){
                    return $obj->getMeta('fb_title');
                }
                if($obj->getMeta('seo_title')){
                    return $obj->getMeta('seo_title');
                }
                return $obj->getTitle();
            }else if($obj instanceof TermModel){
                return OptionHelper::getOption('default_title', get_bloginfo( 'name' )) . ': ' . $obj->getName();
            }
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
	 * @param PostModel|TermModel $obj
     *
     * @return string
	 */
	public static function getDescription($obj = null){
		if($obj){
            if($obj instanceof PostModel){
                if($obj->getMeta('fb_description')){
                    return $obj->getMeta('description');
                }
                if($obj->getMeta('description')){
                    return $obj->getMeta('description');
                }
                if($obj->getMeta('seo_description')){
                    return $obj->getMeta('seo_description');
                }
                return $obj->getExcerpt();
            }else if($obj instanceof TermModel){
                if($obj->getDescription()){
                    return $obj->getDescription();
                }
            }
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
	 * @param PostModel|TermModel $obj
     *
     * @return String
	 */
	public static function getImages($obj = null){
		$images = array();
		if($obj){
            if($obj instanceof PostModel){
	            if(ThumbnailHelper::isSetUp()){
		            $images[] = ThumbnailHelper::getPostThumbnailUrl($obj);
		            $images[] = ThumbnailHelper::getSiteThumbnailUrl();
	            }else{
		            $attachments = $obj->getAttachments('image');
		            $thumbId     = $obj->getThumbnailId();

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
            }else if($obj instanceof TermModel){
	            if(ThumbnailHelper::isSetUp()){
		            $images[] = ThumbnailHelper::getTaxonomyThumbnailUrl($obj);
		            $images[] = ThumbnailHelper::getSiteThumbnailUrl();
	            }
            }
		}else{
			if(ThumbnailHelper::isSetUp()){
				$images[] = ThumbnailHelper::getSiteThumbnailUrl();
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
		return (Util::isHttps()?'https':'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
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
	 * @param PostModel|TermModel $obj
	 *
	 * @return array
	 */
	public static function getFbData($obj = null){
		$data = array();
		$admins = self::getAppAdmins();
		if($admins){
			$data['admins']=$admins;
		}
		$appId = self::getAppID();
		if($appId){
			$data['app_id']=$appId;
		}
		$title = self::getTitle($obj);
		if($title){
			$data['title']=$title;
		}
		$desc = self::getDescription($obj);
		if($desc){
			$data['description']=$desc;
		}
		$url = self::getUrl($obj);
		if($url){
			$data['url']= $url;
		}
		$images = self::getImages($obj);
		if($images){
			$data['images']= $images;
		}
		$type = self::getType();
		if($type){
			$data['type']= $type;
		}
        if($obj){
            if($obj instanceof PostModel){
                $author = self::getAuthor($obj);
                if($author){
                    $data['author']= $author;
                }
                $data['post']=$obj;
            }else if($obj instanceof TermModel){
                $data['term']=$obj;
            }
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
				$avatarUrl = sprintf('//graph.facebook.com/%s/picture?type=square&width=%d&height=%d', $metaFbUseId, (int)$size, (int)$size);
				$avatarUrl2x = sprintf('//graph.facebook.com/%s/picture?type=square&width=%d&height=%d', $metaFbUseId, (int)$size*2, (int)$size*2);
				return preg_replace(array(
					"%src='[^']*'%",
					"%srcset='[^']*'%",
				), array(
					"src='$avatarUrl'",
					"srcset='$avatarUrl2x 2x'",
				), $avatar);
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
	public static function filterMarkCommentWithFbUserId($comment){
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
	public static function filterApproveFbUserComment($approved, $rawComment){
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