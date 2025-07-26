<?php

namespace TypechoPlugin\Restful;

use ReflectionClass;
use ReflectionMethod;
use Typecho\Common;
use Typecho\Config;
use Typecho\Cookie;
use Typecho\Db;
use Typecho\Request as TypechoRequest;
use Typecho\Router;
use Typecho\Widget\Request;
use Utils\Markdown;
use Widget\ActionInterface;
use Widget\Base\Comments;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Action extends Request implements ActionInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Db
     */
    private $db;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var array
     */
    private $httpParams;

    protected \Typecho\Widget\Request $request;
    protected \Typecho\Widget\Response $response;

    public function __construct($request, $response, $params = null)
    {
        $typecho_request = TypechoRequest::getInstance();
        parent::__construct($typecho_request, $params);

        $this->db = Db::get();
        $this->options = Options::alloc();
        $this->config = $this->options->plugin('Restful');
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * 获取路由参数
     *
     * @return array
     */
    public static function getRoutes()
    {
        $routes = array();
        $reflectClass = new ReflectionClass(__CLASS__);
        $prefix = defined('__TYPECHO_RESTFUL_PREFIX__') ? __TYPECHO_RESTFUL_PREFIX__ : '/api/';

        foreach ($reflectClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectMethod) {
            $methodName = $reflectMethod->getName();

            preg_match('/(.*)Action$/', $methodName, $matches);
            if (isset($matches[1])) {
                $routes[] = array(
                    'action' => $matches[0],
                    'name' => 'rest_' . $matches[1],
                    'shortName' => $matches[1],
                    'uri' => $prefix . $matches[1],
                    'description' => trim(str_replace(
                        array('/', '*'),
                        '',
                        substr($reflectMethod->getDocComment(), 0, strpos($reflectMethod->getDocComment(), '@'))
                    )),
                );
            }
        }
        return $routes;
    }

    public function execute()
    {
        $this->sendCORS();
        $this->parseRequest();

        // 1.3不会调用、手动调用方法
        $url = $this->request->getPathInfo();
        $url = str_replace('/api/', '', $url);
        $this->{$url . "Action"}();
    }

    public function action()
    {
    }

    /**
     * 发送跨域 HEADER
     */
    private function sendCORS()
    {
        $httpHost = $this->request->getServer('HTTP_HOST');
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $allowedHttpOrigins = explode("\n", str_replace("\r", "", $this->config->offsetGet('origin')));

        if (!$httpHost) {
            $this->throwError('非法请求！');
        }

        if (in_array($httpHost, $allowedHttpOrigins)) {
            $this->response->setHeader('Access-Control-Allow-Origin', $httpHost);
        }

        if (strtolower($this->request->getServer('REQUEST_METHOD')) == 'options') {
            $this->response->setStatus(204);
            $this->response->setHeader('Access-Control-Allow-Headers', 'Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type');
            $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            exit;
        }
    }

    /**
     * 解析请求参数
     *
     * @return void
     */
    private function parseRequest()
    {
        if ($this->request->isPost()) {
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->throwError('Parse JSON error');
            }
            $this->httpParams = $data;
        }
    }

    /**
     * 获取 GET/POST 参数
     *
     * @param string $key 目标参数的 key
     * @param mixed $default 返回的默认值
     * @return mixed
     */
    private function getParams($key, $default = null)
    {
        if ($this->request->isGet()) {
            return $this->request->get($key, $default);
        }
        if (!isset($this->httpParams[$key])) {
            return $default;
        }
        return $this->httpParams[$key];
    }

    /**
     * 以 JSON 格式返回错误
     *
     * @param string $message 错误信息
     * @param integer $status HTTP 状态码
     * @return void
     */
    private function throwError($message = 'unknown', $status = 400)
    {
        $this->response->setStatus($status);
        $this->response->throwJson(array(
            'status' => 'error',
            'message' => $message,
            'data' => null,
        ));
    }

    /**
     * 以 JSON 格式响应请求的信息
     *
     * @param mixed $data 要返回给用户的内容
     * @return void
     */
    private function throwData($data)
    {
        $this->response->throwJson(array(
            'status' => 'success',
            'message' => '',
            'data' => $data,
        ));
    }

    /**
     * 锁定 API 请求方式
     *
     * @param string $method 请求方式 (get/post)
     * @return void
     */
    private function lockMethod($method)
    {
        $method = strtolower($method);

        if (strtolower($this->request->getServer('REQUEST_METHOD')) != $method) {
            $this->throwError('method not allowed', 405);
        }
    }

    /**
     * show errors when accessing a disabled API
     *
     * @param string $route
     * @return void
     */
    private function checkState($route)
    {
        $state = $this->config->$route;
        if (!$state) {
            $this->throwError('This API has been disabled.', 403);
        }
        $token = $this->request->getHeader('token');
        // 为空不校验
        if (!empty($this->config->apiToken)) {
            // 校验token
            if (empty($token) || $token != $this->config->apiToken) {
                $this->throwError('apiToken is invalid', 403);
            }
        }
    }

    /**
     * 获取文章自定义字段内容
     *
     * @param int $cid
     * @return array
     */
    public function getCustomFields($cid)
    {
        $cfg = $this->config->fieldsPrivacy;
        $filters = empty($cfg) ? array() : explode(',', $cfg);

        $query = $this->db->select('*')->from('table.fields')
            ->where('cid = ?', $cid);
        $rows = $this->db->fetchAll($query);
        $result = array();
        if (count($rows) > 0) {
            foreach ($rows as $key => $value) {
                if (in_array($value['name'], $filters)) {
                    continue;
                }
                $type = $value['type'];
                $result[$value['name']] = array(
                    "name" => $value['name'],
                    "type" => $value['type'],
                    "value" => $value['str_value'],
                );
            }
        }

        return $result;
    }

    /**
     * 获取文章列表、搜索文章的接口
     *
     * @return void
     */
    public function postsAction()
    {
        $this->lockMethod('get');
        $this->checkState('posts');

        $pageSize = $this->getParams('pageSize', 5);
        $page = $this->getParams('page', 1);
        $page = is_numeric($page) ? $page : 1;
        $offset = $pageSize * ($page - 1);

        $filterType = trim($this->getParams('filterType', ''));
        $filterSlug = trim($this->getParams('filterSlug', ''));
        $showContent = trim($this->getParams('showContent', '')) === 'true';
        $showDigest = trim($this->getParams('showDigest', ''));

        if (in_array($filterType, array('category', 'tag', 'search'))) {
            if ($filterSlug == '') {
                $this->throwError('filter slug is empty');
            }

            if ($filterType != 'search') {
                $select = $this->db->select('mid')
                    ->from('table.metas')
                    ->where('type = ?', $filterType)
                    ->where('slug = ?', $filterSlug);

                $row = $this->db->fetchRow($select);
                if (!isset($row['mid'])) {
                    $this->throwError('unknown slug name');
                }
                $mid = $row['mid'];
                $select = $this->db->select('cid')->from('table.relationships')
                    ->where('mid = ?', $mid);

                $cids = $this->db->fetchAll($select);

                if (count($cids) == 0) {
                    $this->throwData(array(
                        'page' => (int)$page,
                        'pageSize' => (int)$pageSize,
                        'pages' => 0,
                        'count' => 0,
                        'dataSet' => array(),
                    ));
                } else {
                    foreach ($cids as $key => $cid) {
                        $cids[$key] = $cid['cid'];
                    }
                }
            }
        }

        $select = $this->db
            ->select('cid', 'title', 'created', 'modified', 'slug', 'commentsNum', 'text', 'type')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->where('created < ?', time())
            ->where('password IS NULL')
            ->order('created', Db::SORT_DESC);
        if (isset($cids)) {
            $cidStr = implode(',', $cids);
            $select->where('cid IN (' . $cidStr . ')');
        } elseif ($filterType == 'search') {
            // Widget_Archive::searchHandle()
            $searchQuery = '%' . str_replace(' ', '%', $filterSlug) . '%';
            $select->where('title LIKE ? OR text LIKE ?', $searchQuery, $searchQuery);
        }

        $count = count($this->db->fetchAll($select));
        $select->offset($offset)
            ->limit($pageSize);
        $result = $this->db->fetchAll($select);
        foreach ($result as $key => $value) {
            // digest has two types
            if ($showDigest === 'more') {
                // if you use 'more', plugin will truncate from <!--more-->
                $result[$key]['digest'] = str_replace(
                    "<!--markdown-->",
                    "",
                    explode("<!--more-->", $result[$key]['text'])[0]
                );

                $result[$key] = $this->articleFilter($result[$key]);
            } elseif ($showDigest === 'excerpt') {
                // if you use 'excerpt', plugin will truncate for certain number of text
                $limit = (int)trim($this->getParams('limit', '200'));
                $result[$key] = $this->articleFilter($value);
                $result[$key]['digest'] = mb_substr(
                    htmlspecialchars_decode(strip_tags($result[$key]['text'])),
                    0,
                    $limit,
                    'utf-8'
                ) . "...";
            } else {
                $result[$key] = $this->articleFilter($value);
            }


            if (!$showContent) {
                unset($result[$key]['text']);
            }
        }

        $this->throwData(array(
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
            'pages' => ceil($count / $pageSize),
            'count' => $count,
            'dataSet' => $result,
        ));
    }

    /**
     * 获取页面列表的接口
     *
     * @return void
     */
    public function pagesAction()
    {
        $this->lockMethod('get');
        $this->checkState('pages');

        $select = $this->db
            ->select('cid', 'title', 'created', 'slug')
            ->from('table.contents')
            ->where('type = ?', 'page')
            ->where('status = ?', 'publish')
            ->where('created < ?', time())
            ->where('password IS NULL')
            ->order('order', Db::SORT_ASC);

        $result = $this->db->fetchAll($select);
        $count = count($result);

        $this->throwData(array(
            'count' => $count,
            'dataSet' => $result,
        ));
    }

    /**
     * 获取分类列表的接口
     *
     * @return void
     */
    public function categoriesAction()
    {
        $this->lockMethod('get');
        $this->checkState('categories');
//        $categories = $this->db->fetchAll(Contents::alloc()->select('*')->where('type = ?', 'page'));
        $categories = $this->db->fetchAll(Metas::alloc()->select('*')->where('type = ?', 'category'));
        $this->throwData($categories);
    }

    /**
     * 获取标签列表的接口
     *
     * @return void
     */
    public function tagsAction()
    {
        $this->lockMethod('get');
        $this->checkState('tags');

        $tags = $this->db
            ->select('mid', 'name', 'slug', 'description', 'count', 'order', 'parent')
            ->from('table.metas')
            ->where("type = 'tag'");
        $result = $this->db->fetchAll($tags);
        if (count($result) != 0) {
            $this->throwData($result);
        } else {
            $this->throwError('no tag', 404);
        }
    }

    /**
     * 获取文章、独立页面详情的接口
     *
     * @return void
     */
    public function postAction()
    {
        $this->lockMethod('get');
        $this->checkState('post');

        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');

        $select = $this->db
            ->select('title', 'cid', 'created', 'type', 'slug', 'commentsNum', 'text')
            ->from('table.contents')
            ->where('password IS NULL');

        if (is_numeric($cid)) {
            $select->where('cid = ?', $cid);
        } else {
            $select->where('slug = ?', $slug);
        }

        $result = $this->db->fetchRow($select);
        if (!empty($result) && count($result) != 0) {
            $result = $this->articleFilter($result);
            $result['csrfToken'] = $this->generateCsrfToken($result['permalink']);
            $this->throwData($result);
        } else {
            $this->throwError('post not exists', 404);
        }
    }

    /**
     * 获取最新（最近）评论的接口
     *
     * @return void
     */
    public function recentCommentsAction()
    {
        $this->lockMethod('get');
        $this->checkState('recentComments');

        $size = $this->getParams('size', 9);
        $query = $this->db
            ->select('coid', 'cid', 'author', 'text')
            ->from('table.comments')
            ->where('type = ? AND status = ?', 'comment', 'approved')
            ->order('created', Db::SORT_DESC)
            ->limit($size);
        $result = $this->db->fetchAll($query);

        $this->throwData(array(
            'count' => count($result),
            'dataSet' => $result
        ));
    }

    /**
     * 获取文章、独立页面评论列表的接口
     *
     * @return void
     */
    public function commentsAction()
    {
        $this->lockMethod('get');
        $this->checkState('comments');

        $pageSize = $this->getParams('pageSize', 5);
        $page = $this->getParams('page', 1);
        $page = is_numeric($page) ? $page : 1;
        $offset = $pageSize * ($page - 1);
        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');
        $order = strtolower($this->getParams('order', ''));

        // 为带 cookie 请求的用户显示正在等待审核的评论
        $author = Cookie::get('__typecho_remember_author');
        $mail = Cookie::get('__typecho_remember_mail');

        if (empty($cid) && empty($slug)) {
            $this->throwError('No specified posts.', 404);
        }

        $select = $this->db
            ->select('table.comments.coid', 'table.comments.parent', 'table.comments.cid', 'table.comments.created', 'table.comments.author', 'table.comments.mail', 'table.comments.url', 'table.comments.text', 'table.comments.status')
            ->from('table.comments')
            ->join('table.contents', 'table.comments.cid = table.contents.cid', Db::LEFT_JOIN)
            ->where('table.comments.type = ?', 'comment')
            ->group('table.comments.coid')
            ->order('table.comments.created', $order === 'asc' ? Db::SORT_ASC : Db::SORT_DESC);

        if (empty($author)) {
            $select->where('table.comments.status = ?', 'approved');
        } else {
            $select
                ->where('table.comments.status = ? OR (table.comments.author = ? AND table.comments.mail = ?)', 'approved', $author, $mail);
        }

        if (is_numeric($cid)) {
            $select->where('table.comments.cid = ?', $cid);
        } else {
            $select->where('table.contents.slug = ?', $slug);
        }

        $result = $this->db->fetchAll($select);

        if (count($result) <= 0) {
            $count = 0;
            $finalResult = array();
        } else {
            $newResult = $this->buildNodes($result);
            $count = count($newResult);

            $finalResult = array_slice($newResult, $offset, $pageSize);
        }

        $this->throwData(array(
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
            'pages' => ceil($count / $pageSize),
            'count' => $count,
            'dataSet' => $finalResult,
        ));
    }

    /**
     * 发表评论的接口
     *
     * @return void
     */
    public function commentAction()
    {
        $this->lockMethod('post');
        $this->checkState('comment');

        $comments = new Comments($this->request, $this->response);
        $check_key = array(
            'text', 'mail', 'author', 'token'
        );
        foreach ($check_key as $key) {
            if (!$this->getParams($key, '')) {
                $this->throwError('missing ' . $key);
            }
        }

        if (!filter_var($this->getParams('mail'), FILTER_VALIDATE_EMAIL)) {
            $this->throwError('邮箱地址不合法');
        }

        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');
        $token = $this->getParams('token', '');

        $select = $this->db->select('cid', 'created', 'type', 'slug', 'commentsNum', 'text')
            ->from('table.contents')
            ->where('password IS NULL');

        if (is_numeric($cid)) {
            $select->where('cid = ?', $cid);
        } else {
            $select->where('slug = ?', $slug);
        }

        $result = $this->db->fetchRow($select);
        if (count($result) != 0) {
            $result = $this->articleFilter($result);
        } else {
            $this->throwError('post not exists', 404);
        }

        if (!$this->checkCsrfToken($result['permalink'], $token)) {
            $this->throwError('token invalid');
        }

        $postData = array(
            'text' => $this->getParams('text', ''),
            'mail' => $this->getParams('mail', ''),
            'cid' => $result['cid'],
            'author' => $this->getParams('author', ''),
        );

        $parent = $this->getParams('parent', '');
        $authorId = $this->getParams('authorId', '');
        $ownerId = $this->getParams('ownerId', '');
        $url = $this->getParams('url', '');

        $uid = Cookie::get('__typecho_uid'); // 登录的话忽略传值
        if (!empty($uid)) {
            $authorId = $uid;
        }

        if (is_numeric($parent)) {
            $postData['parent'] = $parent;
        }
        if (is_numeric($authorId)) {
            $postData['authorId'] = $authorId;
        }
        if (is_numeric($ownerId)) {
            $postData['ownerId'] = $ownerId;
        }
        if (is_string($url)) {
            $postData['url'] = $url;
        }
        $comments->insert($postData);
        $query = $this->db->select()
            ->from('table.comments')
            ->where('author = ?', $this->getParams('author', ''))
            ->order('created', Db::SORT_DESC);
        $res = $this->db->fetchRow($query);
        $this->throwData($res);
    }

    /**
     * 获取设置项的接口
     *
     * @return void
     */
    public function settingsAction()
    {
        $this->lockMethod('get');
        $this->checkState('settings');

        $key = trim($this->getParams('key', ''));
        $allowed = array_merge(explode(',', $this->config->allowedOptions), array(
            'title', 'description', 'keywords', 'timezone',
        ));

        if (!empty($key)) {
            if (in_array($key, $allowed)) {
                $query = $this->db->select('*')
                    ->from('table.options')
                    ->where('name = ?', $key);
                $this->throwData($this->db->fetchAll($query));
            } else {
                $this->throwError('The options key you requested is therefore not allowed.', 403);
            }
        }

        $this->throwData(array(
            'title' => $this->options->title,
            'description' => $this->options->description,
            'keywords' => $this->options->keywords,
            'timezone' => $this->options->timezone,
        ));
    }

    /**
     * 获取作者信息和作者文章的接口
     *
     * @return void
     */
    public function usersAction()
    {
        $this->lockMethod('get');
        $this->checkState('users');

        $uid = $this->getParams('uid', '');
        $name = $this->getParams('name', '');
        if (empty($uid) && empty($name)) {
            $this->throwError('uid or name is required');
        }

        $select = $this->db->select('uid', 'mail', 'url', 'screenName')
            ->from('table.users');
        if (!empty($uid)) {
            $select->where('uid = ?', $uid);
        } elseif (!empty($name)) {
            $select->where('name = ? OR screenName = ?', $name, $name);
        }

        $result = $this->db->fetchAll($select);
        $users = array();
        foreach ($result as $key => $value) {
            $postSelector = $this->db->select('cid', 'title', 'slug', 'created', 'modified', 'type')
                ->from('table.contents')
                ->where('status = ?', 'publish')
                ->where('password IS NULL')
                ->where('type = ?', 'post')
                ->where('authorId = ?', $value['uid']);
            $posts = $this->db->fetchAll($postSelector);
            foreach ($posts as $postNumber => $post) {
                $posts[$postNumber] = $this->articleFilter($post);
            }

            array_push($users, array(
                "uid" => $value['uid'],
                "name" => $value['screenName'],
                "mailHash" => md5($value['mail']),
                "url" => $value['url'],
                "count" => count($posts),
                "posts" => $posts,
            ));
        }

        $this->throwData(array(
            "count" => count($users),
            "dataSet" => $users,
        ));
    }

    /**
     * 归档页面接口
     *
     * @return void
     */
    public function archivesAction()
    {
        $this->lockMethod('get');
        $this->checkState('archives');
        $showContent = trim($this->getParams('showContent', '')) === 'true';
        $order = strtolower($this->getParams('order', 'desc'));
        $showDigest = trim($this->getParams('showDigest', ''));

        $select = $this->db->select('cid', 'title', 'slug', 'created', 'modified', 'type', 'text')
            ->from('table.contents')
            ->where('status = ?', 'publish')
            ->where('password IS NULL')
            ->where('type = ?', 'post')
            ->order('created', $order === 'asc' ? Db::SORT_ASC : Db::SORT_DESC);
        $posts = $this->db->fetchAll($select);

        $archives = array();
        $created = array();
        foreach ($posts as $key => $post) {
            // digest related
            if ($showDigest === 'more') {
                $post['digest'] = str_replace(
                    "<!--markdown-->",
                    "",
                    explode("<!--more-->", $post['text'])[0]
                );

                $post = $this->articleFilter($post);
            } elseif ($showDigest === 'excerpt') {
                $limit = (int)trim($this->getParams('limit', '200'));
                $post = $this->articleFilter($post);
                $post['digest'] = mb_substr(
                    htmlspecialchars_decode(strip_tags($post['text'])),
                    0,
                    $limit,
                    'utf-8'
                ) . "...";
            } else {
                $post = $this->articleFilter($post);
            }

            if (!$showContent) {
                unset($post['text']);
            }

            $date = $post['created'];
            $year = date('Y', $date);
            $month = date('m', $date);
            $archives[$year] = isset($archives[$year]) ? $archives[$year] : array();
            $archives[$year][$month] = isset($archives[$year][$month])
                ? $archives[$year][$month]
                : array();
            array_push($archives[$year][$month], $post);
        }

        // sort by date descend / ascend
        if ($order !== 'asc') {
            krsort($archives, SORT_NUMERIC);
            foreach ($archives as $archive) {
                krsort($archive, SORT_NUMERIC);
            }
        } else {
            ksort($archives, SORT_NUMERIC);
            foreach ($archives as $archive) {
                ksort($archive, SORT_NUMERIC);
            }
        }

        $this->throwData(array(
            "count" => count($posts),
            "dataSet" => $archives,
        ));
    }

    /**
     * 获取用户列表
     *
     * @return void
     */
    public function userListAction()
    {
        $this->lockMethod('get');
        $this->checkState('userList');

        $select = $this->db->select('uid', 'mail', 'url', 'screenName')
            ->from('table.users');
        $result = $this->db->fetchAll($select);
        $this->throwData($result);
    }

    /**
     * 发表文章
     */
    public function postArticleAction()
    {
        $this->lockMethod('post');
        $this->checkState('postArticle');

        if ($this->config->validateLogin == 1 && !$this->widget('Widget_User')->hasLogin()) {
            $this->throwError('User must be logged in', 401);
        }

        $contents = new Contents($this->request, $this->response);

        $check_key = array(
            'title', 'text', 'authorId'
        );
        foreach ($check_key as $key) {
            if (!$this->getParams($key, '')) {
                $this->throwError('missing ' . $key);
            }
        }
        $title = $this->getParams('title', '');
        $text = $this->getParams('text', '');
        $authorId = $this->getParams('authorId', '');
        $slug = $this->getParams('slug', '');
        $mid = $this->getParams('mid', '');

        try {
            $article = $this->db->select('cid', 'created', 'type', 'slug', 'commentsNum', 'text')
                ->from('table.contents')
                ->where('authorId = ?', $authorId);
            if (!empty($slug)) {
                $article->where('slug = ?', $slug);
            } else {
                $article->where('title = ?', $title);
            }
            $articleData = $this->db->fetchRow($article);

            $postData = array(
                'title' => $title,
                'text' => $text,
                'authorId' => $authorId,
                'slug' => $slug,
            );
            $type = 'add';
            if (!empty($articleData)) {
                // 更新
                $postData['modified'] = $this->options->time;
                $res = $this->db->query($this->db->sql()->where('cid = ?', $articleData['cid'])->update('table.contents')->rows($postData));
                $cid = $articleData['cid'];
                $type = 'update';
            } else {
                // 新增
                $res = $cid = $contents->insert($postData);
            }
            // 分类/标签
            if (!empty($mid)) {
                if ($type == 'update') {
                    $metas = $this->db->fetchAll($this->db->select('mid')->from('table.metas'));
                    $sql = $this->db->sql()
                        ->delete('table.relationships')
                        ->where('cid = ?', $cid);
                    if (!empty($metas)) {
                        $sql = $sql->where('mid IN (' . implode(',', array_column($metas, 'mid')) . ')');
                    }
                    $this->db->query($sql);
                }

                $midArray = explode(',', $mid);
                $values = array();
                foreach ($midArray as $mid) {
                    $values[] = '(' . $cid . ', ' . $mid . ')';
                }
                $valuesString = implode(', ', $values);
                $sql = "INSERT INTO " . $this->db->getPrefix() . "relationships (`cid`, `mid`) VALUES " . $valuesString . ";";
                $this->db->query($sql);

                $this->refreshMetas($midArray);
            }

            $this->throwData($res);
        } catch (\Typecho\Db\Exception $e) {
            $this->throwError($e->getMessage());
        }
    }

    /**
     * 新增标签/分类
     */
    public function addMetasAction()
    {
        $this->lockMethod('post');
        $this->checkState('addMetas');
        if ($this->config->validateLogin == 1 && !$this->widget('Widget_User')->hasLogin()) {
            $this->throwError('User must be logged in', 401);
        }
        $name = $this->getParams('name', '');
        $type = $this->getParams('type', '');
        $slug = $this->getParams('slug', '');
        if (empty($name) || empty($type)) {
            $this->throwError('missing name or type');
        }
        if ($type != 'category' && $type != 'tag') {
            $this->throwError('type must be category or tag');
        }
        $res = $this->db->query($this->db->insert('table.metas')->rows(array(
            'name' => $name,
            'type' => $type,
            'slug' => empty($slug) ? $name : $slug,
        )));
        $this->throwData($res);
    }

    /**
     * 插件更新接口
     *
     * @return void
     */
    public function upgradeAction()
    {
        $this->lockMethod('get');

        $isAdmin = call_user_func(function () {
            $hasLogin = $this->widget('Widget_User')->hasLogin();
            $isAdmin = false;
            if (!$hasLogin) {
                return false;
            }
            $isAdmin = $this->widget('Widget_User')->pass('administrator', true);
            return $isAdmin;
        }, $this);

        if (!$isAdmin) {
            $this->throwError('must be admin');
        }

        $localPluginPath = __DIR__ . '/Plugin.php';
        $localActionPath = __DIR__ . '/Action.php';
        $localPluginContent = file_get_contents($localPluginPath);
        $localActionContent = file_get_contents($localActionPath);

        $remotePluginContent = file_get_contents('https://raw.githubusercontent.com/moefront/typecho-plugin-Restful/master/Plugin.php');
        $remoteActionContent = file_get_contents('https://raw.githubusercontent.com/moefront/typecho-plugin-Restful/master/Action.php');

        if (!$remotePluginContent || !$remoteActionContent) {
            $this->throwError('unable to connect to GitHub');
        }

        if (md5($localPluginContent) != md5($remotePluginContent)
            || md5($localActionContent) != md5($remoteActionContent)) {
            if (file_put_contents($localPluginPath, $remotePluginContent)
                && file_put_contents($localActionPath, $remoteActionContent)) {
                $this->throwData(null);
            } else {
                $this->throwError('upgrade failed');
            }
        }
    }

    /**
     * 构造文章评论关系树
     *
     * @param array $raw 评论的集合
     * @return array          返回构造后的评论关系数组
     */
    private function buildNodes($comments)
    {
        $childMap = array();
        $parentMap = array();
        $tree = array();

        foreach ($comments as $index => $comment) {
            $comments[$index]['mailHash'] = md5($comment['mail']);
            unset($comments[$index]['mail']); // avoid exposing users' email to public

            $parent = (int)$comment['parent'];
            if ($parent !== 0) {
                if (!isset($childMap[$parent])) {
                    $childMap[$parent] = array();
                }
                array_push($childMap[$parent], $index);
            } else {
                array_push($parentMap, $index);
            }
        }

        $tree = $this->recursion($comments, $parentMap, $childMap);
        return $tree;
    }

    /**
     * 通过递归构建评论父子关系
     *
     * @param array $comments 评论集合
     * @param array $parents 父评论的 key 集合
     * @param array $map 子评论与父评论的映射关系
     * @return array          返回处理后的结果集合
     */
    private function recursion($comments, $parents, $map)
    {
        $result = array();

        foreach ($parents as $parent) {
            $item = &$comments[$parent];
            $coid = (int)$item['coid'];
            if (isset($map[$coid])) {
                $item['children'] = $this->recursion($comments, $map[$coid], $map);
            } else {
                $item['children'] = array();
            }
            array_push($result, $item);
        }

        return $result;
    }

    /**
     * Safely parse markdown into HTML
     * @param string|null $content
     * @return string
     */
    private function safelyParseMarkdown($content)
    {
        if (is_null($content) || empty($content)) {
            return '';
        }
        return Markdown::convert($content);
    }

    /**
     * 过滤和补全文章数组
     *
     * @param array $value 文章详细信息数组
     * @return array
     */
    private function articleFilter($value)
    {
        $contentWidget = Contents::alloc();
        $value['text'] = isset($value['text']) ? $value['text'] : null;
        $value['digest'] = isset($value['digest']) ? $value['digest'] : null;

        $value['password'] = '';        // typecho:#94ddb69 compat

        $value['type'] = isset($value['type']) ? $value['type'] : null; // Typecho 0.9 compatibility
        $value = $contentWidget->filter($value);
        $value['text'] = $this->safelyParseMarkdown($value['text']);
        $value['digest'] = $this->safelyParseMarkdown($value['text']);

        if ($value['type'] === null) {
            unset($value['type']);
        }
        if (empty(trim($value['text']))) {
            unset($value['text']);
        }
        if (empty(trim($value['digest']))) {
            unset($value['digest']);
        }
        // Custom fields
        $value['fields'] = $this->getCustomFields($value['cid']);

        // 生成permalink，1.3源码没有生成
        $type = $value['type'];
        $routeExists = (null != Router::get($type));
        $value['pathinfo'] = $routeExists ? Router::url($type, $value) : '#';
        $value['url'] = $value['permalink'] = Common::url($value['pathinfo'], $this->options->index);
        // 补充日期
        $value['year'] = $value['date']->year;
        $value['month'] = $value['date']->month;
        $value['day'] = $value['date']->day;

        return $value;
    }

    /**
     * 生成 CSRF Token
     *
     * @param mixed $key
     * @return string
     */
    private function generateCsrfToken($key)
    {
        return base64_encode(
            hash_hmac(
                'sha256',
                hash_hmac(
                    'sha256',
                    date('Ymd') . $this->request->getServer('REMOTE_ADDR') . $this->request->getServer('HTTP_USER_AGENT'),
                    hash('sha256', $key, true),
                    true
                ),
                $this->config->csrfSalt,
                true
            )
        );
    }

    /**
     * 检查 CSRF Token 是否匹配
     *
     * @param mixed $key
     * @param mixed $token
     * @return boolean
     */
    private function checkCsrfToken($key, $token)
    {
        return hash_equals($token, $this->generateCsrfToken($key));
    }

    /**
     * 刷新Metas数量
     * @param array $midArray
     * @return void
     */
    private function refreshMetas(array $midArray)
    {
        $tags = $this->db
            ->select('*')
            ->from('table.metas')
            ->where('mid IN (' . implode(',', $midArray) . ')');
        $result = $this->db->fetchAll($tags);
        // 更新数量
        foreach ($result as $tag) {
            $count = $this->db->fetchObject($this->db->select(array('COUNT(cid)' => 'num'))
                ->from('table.relationships')
                ->where('mid = ?', $tag['mid']))->num;

            $this->db->query($this->db->update('table.metas')
                ->rows(array('count' => $count))
                ->where('mid = ?', $tag['mid']));
        }
    }
}
