<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Restful_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * @var Typecho_Config
     */
    private $config;

    /**
     * @var Typecho_Db
     */
    private $db;

    /**
     * @var Widget_Options
     */
    private $options;

    /**
     * @var array
     */
    private $httpParams;

    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);

        $this->db = Typecho_Db::get();
        $this->options = $this->widget('Widget_Options');
        $this->config = $this->options->plugin('Restful');
    }

    /**
     * 获取路由参数
     *
     * @return array
     */
    public static function getRoutes()
    {
        $routes = [];
        $reflectClass = new ReflectionClass(__CLASS__);
        foreach ($reflectClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectMethod) {
            $methodName = $reflectMethod->getName();

            preg_match('/(.*)Action$/', $methodName, $matches);
            if (isset($matches[1])) {
                array_push($routes, [
                    'action' => $matches[0],
                    'name' => 'rest_' . $matches[1],
                    'shortName' => $matches[1],
                    'uri' => '/api/' . $matches[1],
                    'description' => trim(str_replace(['/', '*'], '',
                        substr($reflectMethod->getDocComment(), 0, strpos($reflectMethod->getDocComment(), '@')))),
                ]);
            }
        }
        return $routes;
    }

    public function execute()
    {
        $this->sendCORS();
        $this->parseRequest();
    }

    public function action()
    {}

    /**
     * 发送跨域 HEADER
     *
     * @return void
     */
    private function sendCORS()
    {
        $httpOrigin = $this->request->getServer('HTTP_ORIGIN');
        $allowedHttpOrigins = explode("\n", $this->config->origin);

        if (!$httpOrigin) {
            return;
        }

        if (in_array($httpOrigin, $allowedHttpOrigins)) {
            $this->response->setHeader('Access-Control-Allow-Origin', $httpOrigin);
        }

        if (strtolower($this->request->getServer('REQUEST_METHOD')) == 'options') {
            Typecho_Response::setStatus(204);
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
        Typecho_Response::setStatus($status);
        $this->response->throwJson([
            'status' => 'error',
            'message' => $message,
            'data' => null,
        ]);
    }

    /**
     * 以 JSON 格式响应请求的信息
     * 
     * @param mixed $data 要返回给用户的内容
     * @return void
     */
    private function throwData($data)
    {
        $this->response->throwJson([
            'status' => 'success',
            'message' => '',
            'data' => $data,
        ]);
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

        if (in_array($filterType, ['category', 'tag', 'search'])) {
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
                    $this->throwData([
                        'page' => (int) $page,
                        'pageSize' => (int) $pageSize,
                        'pages' => 0,
                        'count' => 0,
                        'dataSet' => [],
                    ]);
                } else {
                    foreach ($cids as $key => $cid) {
                        $cids[$key] = $cids[$key]['cid'];
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
            ->order('created', Typecho_Db::SORT_DESC);
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
            if (!$showContent) {
                unset($result[$key]['text']);
            }
            $result[$key] = $this->filter($result[$key]);
        }

        $this->throwData([
            'page' => (int) $page,
            'pageSize' => (int) $pageSize,
            'pages' => ceil($count / $pageSize),
            'count' => $count,
            'dataSet' => $result,
        ]);
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
            ->order('order', Typecho_Db::SORT_ASC);

        $result = $this->db->fetchAll($select);
        $count = count($result);

        $this->throwData([
            'count' => $count,
            'dataSet' => $result,
        ]);
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
        $categories = $this->widget('Widget_Metas_Category_List');

        if (isset($categories->stack)) {
            $this->throwData($categories->stack);
        } else {
            $reflect = new ReflectionObject($categories);
            $map = $reflect->getProperty('_map');
            $map->setAccessible(true);
            $this->throwData(array_merge($map->getValue($categories)));
        }
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

        $this->widget('Widget_Metas_Tag_Cloud')->to($tags);

        if ($tags->have()) {
            while ($tags->next()) {
                $this->throwData($tags->stack);
            }
        }

        $this->throwError('no tag', 404);
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
            ->select('cid', 'created', 'type', 'slug', 'commentsNum', 'text')
            ->from('table.contents')
            ->where('password IS NULL');

        if (is_numeric($cid)) {
            $select->where('cid = ?', $cid);
        } else {
            $select->where('slug = ?', $slug);
        }

        $result = $this->db->fetchRow($select);
        if (count($result) != 0) {
            $result = $this->filter($result);
            $result['csrfToken'] = $this->generateCsrfToken($result['permalink']);
            $this->throwData($result);
        } else {
            $this->throwError('post not exists', 404);
        }

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

        $select = $this->db
            ->select('table.comments.coid', 'table.comments.parent', 'table.comments.cid', 'table.comments.created', 'table.comments.author', 'table.comments.mail', 'table.comments.url', 'table.comments.text')
            ->from('table.comments')
            ->join('table.contents', 'table.comments.cid = table.contents.cid', Typecho_Db::LEFT_JOIN)
            ->where('table.comments.type = ?', 'comment')
            ->where('table.comments.status = ?', 'approved')
            ->group('table.comments.coid')
            ->order('table.comments.created', $order === 'asc' ? Typecho_Db::SORT_ASC : Typecho_Db::SORT_DESC);

        if (is_numeric($cid)) {
            $select->where('table.comments.cid = ?', $cid);
        } else {
            $select->where('table.contents.slug = ?', $slug);
        }

        $result = $this->db->fetchAll($select);

        $newResult = $this->findChild($result, 'coid', 'parent');
        foreach ($newResult as $index => $comment) {
            if (isset($comment['parent']) && $comment['parent'] != 0) {
                unset($newResult[$index]);
            }
        }
        $newResult = array_merge($newResult);
        $count = count($newResult);

        $finalResult = array_slice($newResult, $offset, $pageSize);

        $this->throwData([
            'page' => (int) $page,
            'pageSize' => (int) $pageSize,
            'pages' => ceil($count / $pageSize),
            'count' => $count,
            'dataSet' => $finalResult,
        ]);
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
            $result = $this->filter($result);
        } else {
            $this->throwError('post not exists', 404);
        }

        if (!$this->checkCsrfToken($result['permalink'], $token)) {
            $this->throwError('token invalid');
        }

        $commentUrl = Typecho_Router::url('feedback',
            ['type' => 'comment', 'permalink' => $result['pathinfo']], $this->options->index);

        $postData = [
            'text' => $this->getParams('text', ''),
            'author' => $this->getParams('author', ''),
            'mail' => $this->getParams('mail', ''),
            'url' => $this->getParams('url', ''),
        ];

        // Typecho 0.9- has no anti-spam security
        if (file_exists(__TYPECHO_ROOT_DIR__ . '/var/Widget/Security.php')) {
            $postData['_'] = $this->widget('Widget_Security')->getToken($result['permalink']);
        }

        $parent = $this->getParams('parent', '');
        if (is_numeric($parent)) {
            $postData['parent'] = $parent;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $commentUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-TYPECHO-RESTFUL-IP: ' . $this->request->getIp(),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->request->getAgent());
        curl_setopt($ch, CURLOPT_REFERER, $result['permalink']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // no verify ssl
        $data = curl_exec($ch);

        if (curl_error($ch)) {
            $this->throwError('comment failed');
        }

        curl_close($ch);

        preg_match('!(<title[^>]*>)(.*)(</title>)!i', $data, $matches);
        if (isset($matches[2]) && $matches[2] == 'Error') {
            preg_match('/<div class=\"container\">(.*?)<\/div>/s', $data, $matches);
            if (isset($matches[1])) {
                $this->throwError(trim($matches[1]));
            }
        }

        $this->throwData(null);
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

        $this->throwData([
            'title' => $this->options->title,
            'description' => $this->options->description,
            'keywords' => $this->options->keywords,
            'timezone' => $this->options->timezone,
        ]);
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

        $select = $this->db->select('uid', 'mail', 'url', 'screenName')
            ->from('table.users');
        if (!empty($uid)) {
            $select->where('uid = ?', $uid);
        } else if (!empty($name)) {
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
                $posts[$postNumber] = $this->filter($post);
            }

            array_push($users, array(
                "uid" => $value['uid'],
                "name" => $value['screenName'],
                "mailHash" => md5($value['mail']),
                "url" => $value['url'],
                "count" => count($posts),
                "posts" => $posts
            ));
        }

        $this->throwData(array(
            "count" => count($users),
            "dataset" => $users
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
        $showContent = trim($this->getParam('showContent', '')) === 'true';
        $order = strtolower($this->getParam('order', ''));

        $select = $this->db->select('cid', 'title', 'slug', 'created', 'modified', 'type', 'text')
            ->from('table.contents')
            ->where('status = ?', 'publish')
            ->where('password IS NULL')
            ->where('type = ?', 'post')
            ->order('created', $order === 'asc' ? Typecho_Db::SORT_ASC : Typecho_Db::SORT_DESC);
        $posts = $this->db->fetchAll($select);

        $archives = array();
        $created = array();
        foreach ($posts as $key => $post) {
            $post = $this->filter($post);
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
        // sort by date descend
        krsort($archives, SORT_NUMERIC);
        foreach ($archives as $archive) {
            krsort($archive, SORT_NUMERIC);
        }

        $this->throwData(array(
            "count" => count($posts),
            "dataSet" => $archives
        ));
    }

    /**
     * 创建子节点树形数组
     *
     * @param array  $ar  邻接列表方式组织的数据
     * @param string $id  数组中作为主键的下标或关联键名
     * @param string $pid 数组中作为父键的下标或关联键名
     *
     * @return array
     */
    private function findChild($ar, $id = 'id', $pid = 'pid')
    {
        foreach ($ar as $v) {
            $t[$v[$id]] = $v;
        }

        foreach ($t as $k => $item) {
            if ($item[$pid]) {
                $t[$item[$pid]]['child'] = &$t[$k];
            }
        }
        return $t;
    }

    /**
     * 过滤和补全文章数组
     * 
     * @param array $value 文章详细信息数组
     * @return array
     */
    private function filter($value)
    {
        $contentWidget = $this->widget('Widget_Abstract_Contents');
        $value['text'] = isset($value['text']) ? $value['text'] : null;

        if (method_exists($contentWidget, 'markdown')) {
            $value = $contentWidget->filter($value);
            $value['text'] = $contentWidget->markdown($value['text']);
        } else {
            // Typecho 0.9 compatibility
            $value['type'] = isset($value['type']) ? $value['type'] : null;
            $value = $contentWidget->filter($value);
            $value['text'] = MarkdownExtraExtended::defaultTransform($value['text']);
            if ($value['type'] === null) {
                unset($value['type']);
            }
            if (empty(trim($value['text']))) {
                unset($value['text']);
            }
        }

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

}
