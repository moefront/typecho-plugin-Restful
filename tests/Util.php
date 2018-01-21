<?php
namespace Typecho\Plugin\Restful\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PDO;
use PDOException;
use PharData;
use ProgressBar\Manager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Util
{
    private static $tmpPath = __DIR__ . '/../tmp/';

    /**
     * 下载Typecho
     *
     * @return void
     */
    public static function downloadTypecho()
    {
        $progressBar = new Manager(0, 1000);

        $url = 'https://github.com/typecho/typecho/archive/master.tar.gz';
        $fileName = basename($url);
        $filePath = self::$tmpPath . $fileName;

        echo 'downloading typecho' . PHP_EOL;

        try {
            self::deleteDir(self::$tmpPath);
        } catch (Exception $e) {}

        self::mkdirs(self::$tmpPath);

        $client = new Client([
            'progress' => function (
                $downloadTotal,
                $downloadedBytes,
                $uploadTotal,
                $uploadedBytes) use ($progressBar) {
                if ($downloadTotal != 0) {
                    $progressBar->update(intval(floor($downloadedBytes / $downloadTotal * 1000)));
                }

            },
        ]);

        $request = new Request('get', $url);
        $promise = $client->sendAsync($request, [
            'sink' => $filePath,
        ]);
        $promise->then(function (Response $resp) {
            echo 'download completed' . PHP_EOL;
        }, function (RequestException $e) {
            throw $e;
        });
        $promise->wait();

        echo 'extracting' . PHP_EOL;

        $pharData = new PharData($filePath);
        $pharData->decompress();
        $pharData = new PharData(substr($filePath, 0, -3));
        $pharData->extractTo(self::$tmpPath);
    }

    /**
     * 安装Typecho
     *
     * @return void
     */
    public static function installTypecho()
    {
        try {
            $pdo = new PDO('mysql:host=' . getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PWD'));
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('DROP DATABASE IF EXISTS `' . getenv('MYSQL_DB') . '`;
CREATE DATABASE `' . getenv('MYSQL_DB') . '`;');
        } catch (PDOException $e) {
            throw $e;
        }

        exec(sprintf('mysql -u %s --password=%s %s < %s',
            getenv('MYSQL_USER'),
            getenv('MYSQL_PWD'),
            getenv('MYSQL_DB'),
            __DIR__ . '/typecho.sql'));

        $typechoDir = self::$tmpPath . 'typecho-master';

        $configFileContent = sprintf('<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/** 定义根目录 */
define("__TYPECHO_ROOT_DIR__", dirname(__FILE__));

/** 定义插件目录(相对路径) */
define("__TYPECHO_PLUGIN_DIR__", "/usr/plugins");

/** 定义模板目录(相对路径) */
define("__TYPECHO_THEME_DIR__", "/usr/themes");

/** 后台路径(相对路径) */
define("__TYPECHO_ADMIN_DIR__", "/admin/");

/** 设置包含路径 */
@set_include_path(get_include_path() . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . "/var" . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__);

/** 载入API支持 */
require_once "Typecho/Common.php";

/** 载入Response支持 */
require_once "Typecho/Response.php";

/** 载入配置支持 */
require_once "Typecho/Config.php";

/** 载入异常支持 */
require_once "Typecho/Exception.php";

/** 载入插件支持 */
require_once "Typecho/Plugin.php";

/** 载入国际化支持 */
require_once "Typecho/I18n.php";

/** 载入数据库支持 */
require_once "Typecho/Db.php";

/** 载入路由器支持 */
require_once "Typecho/Router.php";

/** 程序初始化 */
Typecho_Common::init();

/** 定义数据库参数 */
$db = new Typecho_Db("Pdo_Mysql", "typecho_");
$db->addServer(array (
  "host" => "%s",
  "user" => "%s",
  "password" => "%s",
  "charset" => "utf8mb4",
  "port" => "3306",
  "database" => "%s",
  "engine" => "InnoDB",
), Typecho_Db::READ | Typecho_Db::WRITE);
Typecho_Db::set($db);
', getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PWD'), getenv('MYSQL_DB'));

        file_put_contents($typechoDir . '/config.inc.php', $configFileContent);

        $pluginDir = $typechoDir . '/usr/plugins/Restful';
        try {
            self::deleteDir($pluginDir);
        } catch (Exception $e) {}
        self::mkdirs($pluginDir);
        copy(__DIR__ . '/../Plugin.php', $pluginDir . '/Plugin.php');
        copy(__DIR__ . '/../Action.php', $pluginDir . '/Action.php');

        file_put_contents($typechoDir . '/reactivate_restful.php', "<?php
require_once __DIR__ . '/index.php';

Restful_Plugin::deactivate();
Restful_Plugin::activate();
");
        file_get_contents('http://' . getenv('WEB_SERVER_HOST') . ':' . getenv('WEB_SERVER_PORT') . '/reactivate_restful.php');
    }

    /**
     * 启动服务器
     *
     * @return void
     */
    public static function startServer()
    {
        // Command that starts the built-in web server
        exec('pid=$(lsof -i:' . getenv('WEB_SERVER_PORT') . ' -t); kill -TERM $pid || kill -KILL $pid 2> /dev/null');
        $command = sprintf(
            'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
            getenv('WEB_SERVER_HOST'),
            getenv('WEB_SERVER_PORT'),
            getenv('WEB_SERVER_DOCROOT')
        );
        echo sprintf('Running command "%s"', $command) . PHP_EOL;
        // Execute the command and store the process ID
        $output = [];
        exec($command, $output);
        return (int) $output[0];
    }

    /**
     * 服务器存活确认
     *
     * @return boolean
     */
    public static function canConnectToServer()
    {
        // Disable error handler for now
        set_error_handler(function () {
            return true;
        });
        // Try to open a connection
        $sp = fsockopen(getenv('WEB_SERVER_HOST'), getenv('WEB_SERVER_PORT'));
        // Restore the handler
        restore_error_handler();
        if ($sp === false) {
            return false;
        }
        fclose($sp);
        return true;
    }

    /**
     * 结束进程
     *
     * @param  integer $pid
     * @return void
     */
    public static function killProcess($pid)
    {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . (int) $pid);
    }

    /**
     * 递归删除目录
     *
     * @param  string  $dirPath 目录路径
     * @param  boolean $removeOnlyChildren
     * @return void
     */
    private static function deleteDir($dirPath, $removeOnlyChildren = false)
    {
        if (empty($dirPath) || file_exists($dirPath) === false) {
            return false;
        }

        if (is_file($dirPath) || is_link($dirPath)) {
            return unlink($dirPath);
        }

        $files = new RecursiveIteratorIterator
            (
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        // $fileinfo as SplFileInfo
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                if (self::deleteDir($fileinfo->getRealPath()) === false) {
                    return false;
                }
            } else {
                if (unlink($fileinfo->getRealPath()) === false) {
                    return false;
                }
            }
        }

        if ($removeOnlyChildren === false) {
            return rmdir($dirPath);
        }

        return true;
    }

    /**
     * 创建目录
     *
     * @param  string  $pathname 目录路径
     * @param  integer $mode     目录权限
     * @return boolean
     */
    private static function mkdirs($pathname, $mode = 0755)
    {
        is_dir(dirname($pathname)) || self::mkdirs(dirname($pathname), $mode);
        return is_dir($pathname) || @mkdir($pathname, $mode); // @codingStandardsIgnoreLine
    }
}
