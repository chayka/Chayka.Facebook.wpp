<?php

namespace Chayka\Facebook;

use Chayka\WP\MVC\Controller;
use Chayka\WP\Queries\TermQuery;

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
        wp_enqueue_media();

        $defaultSiteThumbnail = [
            'name' => 'Site Thumbnail',
            'type' => 'site',
            'background' => [
                'imageMode' => 'default',
                'url' => '',
            ],
            'logo' => [
                'active' => true,
                'imageMode' => 'default',
                'url' => '',
                'x' => 50,
                'unitX' => '%',
                'y' => 10,
                'unitY' => '%',
                'width' => 30,
                'unitWidth' => '%',
                'anchor' => 'center-top',
            ],
            'fade' => [
                'active' => true,
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 50,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],
            'site_title' => [
                'active' => true,
                'color' => '#FFFFFF',
                'fontFamily' => 'default',
                'fontSize' => 32,
                'textAlign' => 'center',
                'anchor' => 'center-bottom',
                'x' => 50,
                'unitX' => '%',
                'y' => 80,
                'unitY' => '%',
                'width' => 90,
                'unitWidth' => '%',
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 0,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],
            'site_description' => [
                'active' => true,
                'color' => '#FFFFFF',
                'fontFamily' => 'default',
                'fontSize' => 20,
                'textAlign' => 'center',
                'anchor' => 'center-top',
                'x' => 50,
                'unitX' => '%',
                'y' => 80,
                'unitY' => '%',
                'width' => 90,
                'unitWidth' => '%',
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 0,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],
        ];

        $defaultPostThumbnail = [
            'name' => 'Default Post Thumbnail',
            'type' => 'post',
            'background' => [
                'imageMode' => 'default',
                'url' => '',
            ],
            'logo' => [
                'active' => true,
                'imageMode' => 'default',
                'url' => '',
                'x' => 50,
                'unitX' => '%',
                'y' => 10,
                'unitY' => '%',
                'width' => 25,
                'unitWidth' => '%',
                'anchor' => 'center-top',
            ],
            'fade' => [
                'active' => true,
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 50,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
            ],
            'title' => [
                'active' => true,
                'color' => '#FFFFFF',
                'fontFamily' => 'default',
                'fontSize' => 32,
                'textAlign' => 'center',
                'anchor' => 'center-center',
                'x' => 50,
                'unitX' => '%',
                'y' => 75,
                'unitY' => '%',
                'width' => 70,
                'unitWidth' => '%',
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 0,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],
        ];

        $defaultTaxonomyThumbnail = [
            'name' => 'Default Taxonomy Thumbnail',
            'type' => 'taxonomy',
            'background' => [
                'imageMode' => 'default',
                'url' => '',
            ],
            'logo' => [
                'active' => true,
                'imageMode' => 'default',
                'url' => '',
                'x' => 50,
                'unitX' => '%',
                'y' => 10,
                'unitY' => '%',
                'width' => 30,
                'unitWidth' => '%',
                'anchor' => 'center-top',
            ],
            'fade' => [
                'active' => true,
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 50,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
            ],
            'taxonomy' => [
                'active' => true,
                'color' => '#FFFFFF',
                'fontFamily' => 'default',
                'fontSize' => 32,
                'textAlign' => 'center',
                'anchor' => 'center-bottom',
                'x' => 50,
                'unitX' => '%',
                'y' => 80,
                'unitY' => '%',
                'width' => 90,
                'unitWidth' => '%',
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 0,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],
            'site_title' => [
                'active' => true,
                'color' => '#FFFFFF',
                'fontFamily' => 'default',
                'fontSize' => 20,
                'textAlign' => 'center',
                'anchor' => 'center-top',
                'x' => 50,
                'unitX' => '%',
                'y' => 82,
                'unitY' => '%',
                'width' => 90,
                'unitWidth' => '%',
                'backgroundColor' => '#000000',
                'backgroundOpacity' => 0,
                'borderColor' => '#FFFFFF',
                'borderWidth' => 0,
                'padding' => 0,
            ],

        ];

        $templates = OptionHelper::getOption('thumbnailTemplates');
        if(!$templates){
            $templates = [
                'site' => $defaultSiteThumbnail,
                'post' => ['default' => $defaultPostThumbnail],
                'taxonomy' => ['default' => $defaultTaxonomyThumbnail]
            ];
            OptionHelper::setOption('thumbnailTemplates', $templates);
        }

        $topCategories = TermQuery::query('category')
            ->order_DESC()
            ->orderBy_Count()
            ->number(3)
            ->fields_Names()
            ->select();

        if(empty($topCategories)){
            $topCategories = ['Articles', 'News', 'Reviews'];
        }

        $topTags = TermQuery::query('post_tag')
            ->order_DESC()
            ->orderBy_Count()
            ->number(3)
            ->fields_Names()
            ->select();

        if(empty($topTags)){
            $topTags = ['life', 'hack', 'mastery'];
        }

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
                'text' => 'The quick brown fox jumps over the lazy dog',
                'type' => 'post',
            ],
            'excerpt' => [
                'title' => 'Excerpt',
                'text' => substr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 0, 253).'...',
                'type' => 'post',
            ],
            'categories' => [
                'title' => 'Categories',
                'text' => $topCategories,
                'type' => 'post',
            ],
            'tags' => [
                'title' => 'Tags',
                'text' => $topTags,
                'type' => 'post',
            ],
            'taxonomy' => [
                'title' => 'Taxonomy',
                'text' => reset($topCategories),
                'type' => 'taxonomy',
            ],
        ];

        $taxonomies = get_taxonomies([
            'public' => true
        ]);

        $postTypes = get_post_types([
            'public' => true
        ]);

        $this->enqueueNgScriptStyle('chayka-facebook-thumbnail-setup');
        $this->view->assign('fonts', FontHelper::getTrueTypeFontNames());
        $this->view->assign('defaultFont', OptionHelper::getOption('thumbnailDefaultFont'));
        $this->view->assign('defaultLogo', OptionHelper::getOption('thumbnailDefaultLogo'));
        $this->view->assign('defaultBackground', OptionHelper::getOption('thumbnailDefaultBackground'));

        $this->view->assign('defaultSiteThumbnail', $defaultSiteThumbnail);
        $this->view->assign('defaultPostThumbnail', $defaultPostThumbnail);
        $this->view->assign('defaultTaxonomyThumbnail', $defaultTaxonomyThumbnail);

        $this->view->assign('templates', $templates);

        $this->view->assign('blocks', $blocks);

        $this->view->assign('postTypes', array_keys($postTypes));
        $this->view->assign('taxonomies', array_keys($taxonomies));
    }
}