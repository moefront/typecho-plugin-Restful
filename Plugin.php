<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
/**
 * Typecho Restful 插件
 *
 * @package Restful
 * @author MoeFront Studio
 * @version 1.0.0
 * @link https://moefront.github.io
 */
class Restful_Plugin implements Typecho_Plugin_Interface
{
    const ACTION_CLASS = 'Restful_Action';

    const ROUTES = [
        'posts',
        'pages',
        'categories',
        'tags',
        'post',
        'comments',
        'comment',
        'settings',
    ];

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        foreach (self::ROUTES as $route) {
            Helper::addRoute('rest_' . $route, '/api/' . $route, self::ACTION_CLASS, $route . 'Action');
        }
        Typecho_Plugin::factory('Widget_Feedback')->comment = [__CLASS__, 'comment'];
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
        foreach (self::ROUTES as $route) {
            Helper::removeRoute('rest_' . $route);
        }
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

    /**
     * 构造评论真实IP
     *
     * @return array
     */
    public static function comment($comment, $post)
    {
        $request = Typecho_Request::getInstance();

        $customIp = $request->getServer('HTTP_X_TYPECHO_RESTFUL_IP');
        if ($customIp != null) {
            $comment['ip'] = $customIp;
        }

        return $comment;
    }

}
