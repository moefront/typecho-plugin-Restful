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

        return '_(:з」∠)_';
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

        return '( ≧Д≦)';
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
        /* API switcher */
        echo '<h3>API 状态设置</h3>';

        $interfaces = array(
            'posts' => '获取文章列表、搜索文章的接口。',
            'pages' => '获取页面列表的接口。',
            'categories' => '获取分类列表的接口。',
            'tags' => '获取标签列表的接口。',
            'post' => '获取文章 / 独立页面详情的接口。',
            'comments' => '获取文章 / 独立页面评论列表的接口。',
            'comment' => '发表评论的接口。',
            'settings' => '获取设置项的接口。'
        );

        foreach ($interfaces as $name => $description) {
            $tmp = new Typecho_Widget_Helper_Form_Element_Radio($name, array(
                0 => _t('禁用'), 1 => _t('启用'),
            ), 1, _t('/api/' . $name), _t($description));
            $form->addInput($tmp);
        }

        $origin = new Typecho_Widget_Helper_Form_Element_Textarea('origin', null, null, _t('域名列表'), _t('一行一个<br>以下是例子qwq<br>http://localhost:8080<br>https://blog.example.com<br>若不限制跨域域名，请使用通配符 *。'));
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
