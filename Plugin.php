<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
/**
 * Typecho Restful 插件
 *
 * @package Restful
 * @author Kokororin
 * @version 1.0.0
 * @link https://kotori.love
 */
class Restful_Plugin implements Typecho_Plugin_Interface
{
    const ACTION_CLASS = 'Restful_Action';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Helper::addRoute('rest_posts', '/api/posts', self::ACTION_CLASS, 'postsAction');
        Helper::addRoute('rest_pages', '/api/pages', self::ACTION_CLASS, 'pagesAction');
        Helper::addRoute('rest_post', '/api/post', self::ACTION_CLASS, 'postAction');
        Helper::addRoute('rest_comments', '/api/comments', self::ACTION_CLASS, 'commentsAction');
        Helper::addRoute('rest_comment', '/api/comment', self::ACTION_CLASS, 'commentAction');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('rest_posts');
        Helper::removeRoute('rest_pages');
        Helper::removeRoute('rest_post');
        Helper::removeRoute('rest_comments');
        Helper::removeRoute('rest_comment');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $origin = new Typecho_Widget_Helper_Form_Element_Textarea('origin', null, null, _t('域名列表'), _t('一行一个<br>以下是例子qwq<br>http://localhost:8080<br>https://blog.example.com'));
        $form->addInput($origin);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}

}
