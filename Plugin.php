<?php
namespace TypechoPlugin\Restful;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Plugin as TypechoPlugin;
use Typecho\Plugin\Exception;
use Typecho\Plugin\PluginInterface;
use Typecho\Request;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Utils\Helper;

/**
 * Typecho Restful 插件
 *
 * @package Restful
 * @author MoeFront Studio
 * @version 1.2.0
 * @link https://moefront.github.io
 */
class Plugin implements PluginInterface
{
    const ACTION_CLASS = 'TypechoPlugin\Restful\Action';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     * @throws Exception
     */
    public static function activate()
    {
        $routes = call_user_func(array(self::ACTION_CLASS, 'getRoutes'));
        foreach ($routes as $route) {
            Helper::addRoute($route['name'], $route['uri'], self::ACTION_CLASS, $route['action']);
        }
        TypechoPlugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'comment');

        return '_(:з」∠)_';
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return string
     * @throws Exception
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
     * @param Form $form 配置面板
     * @return void
     */
    public static function config(Form $form)
    {
        echo '<button type="button" class="btn" style="outline: 0" onclick="restfulUpgrade(this)">' . _t('检查并更新插件'). '</button>';

        $prefix = defined('__TYPECHO_RESTFUL_PREFIX__') ? __TYPECHO_RESTFUL_PREFIX__ : '/api/';
        /* API switcher */
        $routes = call_user_func(array(self::ACTION_CLASS, 'getRoutes'));
        echo '<h3>API 状态设置</h3>';

        foreach ($routes as $route) {
            if ($route['shortName'] == 'upgrade') {
                continue;
            }
            $tmp = new Radio($route['shortName'], array(
                0 => _t('禁用'),
                1 => _t('启用'),
            ), 1, $route['uri'], _t($route['description']));
            $form->addInput($tmp);
        }
        /* cross-origin settings */
        $origin = new Textarea('origin', null, null, _t('域名列表'), _t('一行一个<br>以下是例子qwq<br>http://localhost:8080<br>https://blog.example.com<br>若不限制跨域域名，请使用通配符 *。'));
        $form->addInput($origin);

        /* custom field privacy */
        $fieldsPrivacy = new Text('fieldsPrivacy', null, null, _t('自定义字段过滤'), _t('过滤掉不希望在获取文章信息时显示的自定义字段名称。使用半角英文逗号分隔，例如 fields1,fields2 .'));
        $form->addInput($fieldsPrivacy);

        /* allowed options attribute */
        $allowedOptions = new Text('allowedOptions', null, null, _t('自定义设置项白名单'), _t('默认情况下 /api/settings 只会返回一些安全的站点设置信息。若有需要你可以在这里指定允许返回的存在于 typecho_options 表中的字段，并通过 ?key= 参数请求。使用半角英文逗号分隔每个 key, 例如 keywords,theme .'));
        $form->addInput($allowedOptions);

        /* CSRF token salt */
        $csrfSalt = new Text('csrfSalt', null, '05faabd6637f7e30c797973a558d4372', _t('CSRF加密盐'), _t('请务必修改本参数，以防止跨站攻击。'));
        $form->addInput($csrfSalt);

        /* API token */
        $apiToken = new Text('apiToken', null, '123456', _t('APITOKEN'), _t('api请求需要携带的token，设置为空就不校验。'));
        $form->addInput($apiToken);

        /* 高敏接口是否校验登录用户 */
        $validateLogin = new Radio('validateLogin', array(
            0 => _t('否'),
            1 => _t('是'),
        ), 0, _t('高敏接口是否校验登录'), _t('开启后，高敏接口需要携带Cookie才能访问'));
        $form->addInput($validateLogin);
        ?>
<script>
function restfulUpgrade(e) {
    var originalText = e.innerHTML;
    var waitingText = '<?php echo _t('请稍后...');?>';
    if (e.innerHTML === waitingText) {
        return;
    }
    e.innerHTML = waitingText;
    var x = new XMLHttpRequest();
    x.open('GET', '<?php echo rtrim(Helper::options()->index, '/') . $prefix . 'upgrade';?>', true);
    x.onload = function() {
        var data = JSON.parse(x.responseText);
        if (x.status >= 200 && x.status < 400) {
            if (data.status === 'success') {
                alert('<?php echo _t('更新成功，您可能需要禁用插件再启用。');?>');
            } else {
                alert(data.message);
            }
        } else {
            alert(data.message);
        }
    };
    x.onerror = function() {
        alert('<?php echo _t('网络异常，请稍后再试');?>');
    };
    x.send();
}
</script>
        <?php
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
     * @return void
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 构造评论真实IP
     *
     * @return array
     */
    public static function comment($comment, $post)
    {
        $request = Request::getInstance();

        $customIp = $request->getServer('HTTP_X_TYPECHO_RESTFUL_IP');
        if ($customIp != null) {
            $comment['ip'] = $customIp;
        }

        return $comment;
    }
}
