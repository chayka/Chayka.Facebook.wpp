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
        $this->enqueueNgScriptStyle('chayka-facebook-thumbnail-post');

        FontHelper::init();

        $url = FontHelper::createCssFile('fonts.css');

        wp_enqueue_style('facebook-gd2-fonts', $url);

        $p = $this->view->post;
        $p->loadTerms();

        $blocks = [
            'site_title' => [
                'title' => 'Site Title',
                'text' => get_bloginfo('site_name')
            ],
            'site_description' => [
                'title' => 'Site Description',
                'text' => get_bloginfo('description')
            ],
            'title' => [
                'title' => 'Title',
                'text' => $p->getTitle()
            ],
            'excerpt' => [
                'title' => 'Excerpt',
                'text' => $p->getExcerpt()
            ],
            'categories' => [
                'title' => 'Categories',
                'text' => $p->getTerms('category')
            ],
            'tags' => [
                'title' => 'Tags',
                'text' => $p->getTerms('post_tag')
            ],
        ];

        $tb = $p->getThumbnailData_Full();

        $this->view->assign('blocks', $blocks);
        $this->view->assign('fonts', FontHelper::getTrueTypeFontNames());
        $this->view->assign('defaultFont', OptionHelper::getOption('thumbnailDefaultFont'));
        $this->view->assign('defaultLogo', OptionHelper::getOption('thumbnailDefaultLogo'));
        $this->view->assign('defaultBackground', $tb?$tb['url']:OptionHelper::getOption('thumbnailDefaultBackground'));

        $this->view->assign('thumbnailWidth', ThumbnailHelper::THUMBNAIL_WIDTH);
        $this->view->assign('thumbnailHeight', ThumbnailHelper::THUMBNAIL_HEIGHT);

        $templates = OptionHelper::getOption('thumbnailTemplates');
        $postTemplates = empty($templates)?null:$templates['post'];
        $this->view->assign('templates', $postTemplates);

    }
}