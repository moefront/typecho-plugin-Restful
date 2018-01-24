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

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $routes = call_user_func(array(self::ACTION_CLASS, 'getRoutes'));
        foreach ($routes as $route) {
            Helper::addRoute($route['name'], $route['uri'], self::ACTION_CLASS, $route['action']);
        }
        Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'comment');

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
        $routes = call_user_func(array(self::ACTION_CLASS, 'getRoutes'));
        foreach ($routes as $route) {
            Helper::removeRoute($route['name']);
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
        $routes = call_user_func(array(self::ACTION_CLASS, 'getRoutes'));
        echo '<h3>API 状态设置</h3>';

        foreach ($routes as $route) {
            $tmp = new Typecho_Widget_Helper_Form_Element_Radio($route['shortName'], array(
                0 => _t('禁用'),
                1 => _t('启用'),
            ), 1, $route['uri'], _t($route['description']));
            $form->addInput($tmp);
        }
        /* cross-origin settings */
        $origin = new Typecho_Widget_Helper_Form_Element_Textarea('origin', null, null, _t('域名列表'), _t('一行一个<br>以下是例子qwq<br>http://localhost:8080<br>https://blog.example.com<br>若不限制跨域域名，请使用通配符 *。'));
        $form->addInput($origin);

        /* custom field privacy */
        $fieldsPrivacy = new Typecho_Widget_Helper_Form_Element_Text('fieldsPrivacy', null, null, _t('自定义字段过滤'), _t('过滤掉不希望在获取文章信息时显示的自定义字段名称。使用半角英文逗号分隔，例如 fields1,fields2 .'));
        $form->addInput($fieldsPrivacy);

        /* allowed options attribute */
        $allowedOptions = new Typecho_Widget_Helper_Form_Element_Text('allowedOptions', null, null, _t('自定义设置项白名单'), _t('默认情况下 /api/settings 只会返回一些安全的站点设置信息。若有需要你可以在这里指定允许返回的存在于 typecho_options 表中的字段，并通过 ?key= 参数请求。使用半角英文逗号分隔每个 key, 例如 keywords,theme .'));
        $form->addInput($allowedOptions);

        /* CSRF token salt */
        $csrfSalt = new Typecho_Widget_Helper_Form_Element_Text('csrfSalt', null, '05faabd6637f7e30c797973a558d4372', _t('CSRF加密盐'), _t('请务必修改本参数，以防止跨站攻击。'));
        $form->addInput($csrfSalt);
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
