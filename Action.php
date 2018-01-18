<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Restful_Action implements Widget_Interface_Do
{
    private $config;
    private $db;
    private $response;
    private $request;
    private $options;
    private $params;

    public function __construct()
    {
        $this->config = Typecho_Widget::widget('Widget_Options')->plugin('Restful');
        $this->db = Typecho_Db::get();
        $this->response = Typecho_Response::getInstance();
        $this->request = Typecho_Request::getInstance();
        $this->options = Helper::options();
    }

    public function execute()
    {
        $this->sendCORS();
        $this->parseRequest();
    }

    public function action()
    {}

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

    private function parseRequest()
    {
        if ($this->request->isPost()) {
            $data = file_get_contents('php://input');
            $data = json_decode($data, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->throwError('Parse JSON error');
            }
            $this->params = $data;
        }
    }

    private function getParams($key, $default = null)
    {
        if ($this->request->isGet()) {
            return $this->request->get($key, $default);
        }
        if (!isset($this->params[$key])) {
            return $default;
        }
        return $this->params[$key];
    }

    private function throwError($message = 'unknown', $status = 400)
    {
        Typecho_Response::setStatus($status);
        $this->response->throwJson([
            'status' => 'error',
            'message' => $message,
            'data' => null,
        ]);
    }

    private function throwData($data)
    {
        $this->response->throwJson([
            'status' => 'success',
            'message' => '',
            'data' => $data,
        ]);
    }

    private function lockMethod($method)
    {
        $method = strtolower($method);
        if (strtolower($this->request->getServer('REQUEST_METHOD')) != $method) {
            $this->throwError('method not allowed', 405);
        }
    }

    public function postsAction()
    {
        $this->lockMethod('get');

        $pageSize = $this->getParams('pageSize', 5);
        $page = $this->getParams('page', 1);
        $page = is_numeric($page) ? $page : 1;
        $offset = $pageSize * ($page - 1);

        $filterType = trim($this->getParams('filterType', ''));
        $filterSlug = trim($this->getParams('filterSlug', ''));
        $showContent = trim($this->getParams('showContent', '')) === 'true';

        if (in_array($filterType, ['category', 'tag'])) {
            if ($filterSlug == '') {
                $this->throwError('filter slug is empty');
            }
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
                    'count' => 0,
                    'dataSet' => [],
                ]);
            } else {
                foreach ($cids as $key => $cid) {
                    $cids[$key] = $cids[$key]['cid'];
                }
            }
        }

        $select = $this->db->select('cid', 'title', 'created', 'modified', 'slug', 'commentsNum', 'text')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->where('created < ?', time())
            ->where('password IS NULL')
            ->order('created', Typecho_Db::SORT_DESC);
        if (isset($cids)) {
            $cidStr = implode(',', $cids);
            $select->where('cid IN (' . $cidStr . ')');
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
            'count' => $count,
            'dataSet' => $result,
        ]);
    }

    public function pagesAction()
    {
        $this->lockMethod('get');

        $select = $this->db->select('cid', 'title', 'created', 'slug')
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

    public function categoriesAction()
    {
        $this->lockMethod('get');
        $categories = Typecho_Widget::widget('Widget_Metas_Category_List');

        if (isset($categories->stack)) {
            $this->throwData($categories->stack);
        } else {
            $reflect = new ReflectionObject($categories);
            $map = $reflect->getProperty('_map');
            $map->setAccessible(true);
            $this->throwData(array_merge($map->getValue($categories)));
        }
    }

    public function tagsAction()
    {
        $this->lockMethod('get');

        Typecho_Widget::widget('Widget_Metas_Tag_Cloud')->to($tags);

        if ($tags->have()) {
            while ($tags->next()) {
                $this->throwData($tags->stack);
            }
        }

        $this->throwError('no tag', 404);
    }

    public function postAction()
    {
        $this->lockMethod('get');
        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');

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
            $this->throwData($result);
        } else {
            $this->throwError('post not exists', 404);
        }

    }

    public function commentsAction()
    {
        $this->lockMethod('get');

        $pageSize = $this->getParams('pageSize', 5);
        $page = $this->getParams('page', 1);
        $page = is_numeric($page) ? $page : 1;
        $offset = $pageSize * ($page - 1);
        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');
        $order = strtolower($this->getParams('order', ''));

        $select = $this->db->select('table.comments.coid', 'table.comments.parent', 'table.comments.cid', 'table.comments.created', 'table.comments.author', 'table.comments.mail', 'table.comments.url', 'table.comments.text')
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
            if ($comment['parent'] != 0) {
                unset($newResult[$index]);
            }
        }
        $newResult = array_merge($newResult);
        $count = count($newResult);

        $finalResult = array_slice($newResult, $offset, $pageSize);

        $this->throwData([
            'page' => (int) $page,
            'pageSize' => (int) $pageSize,
            'count' => $count,
            'dataSet' => $finalResult,
        ]);
    }

    public function commentAction()
    {
        $this->lockMethod('post');

        $slug = $this->getParams('slug', '');
        $cid = $this->getParams('cid', '');

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

        $commentUrl = Typecho_Router::url('feedback',
            ['type' => 'comment', 'permalink' => $result['pathinfo']], $this->options->index);

        $postData = [
            'text' => $this->getParams('text', ''),
            'author' => $this->getParams('author', ''),
            'mail' => $this->getParams('mail', ''),
            'url' => $this->getParams('url', ''),
            '_' => Helper::security()->getToken($result['permalink']),
        ];

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

    public function settingsAction()
    {
        $this->lockMethod('get');

        $this->throwData([
            'title' => $this->options->title,
            'description' => $this->options->description,
            'keywords' => $this->options->keywords,
            'timezone' => $this->options->timezone,
        ]);
    }

    /**
     * 创建子节点树形数组
     * 参数
     * $ar 数组，邻接列表方式组织的数据
     * $id 数组中作为主键的下标或关联键名
     * $pid 数组中作为父键的下标或关联键名
     * 返回 多维数组
     **/
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

    private function filter($value)
    {
        $contentWidget = Typecho_Widget::widget('Widget_Abstract_Contents');

        if (method_exists($contentWidget, 'markdown')) {
            $value = $contentWidget->filter($value);
            $value['text'] = $contentWidget->markdown($value['text']);
        } else {
            // Typecho 0.9 compatibility
            $value['type'] = isset($value['type']) ? $value['type'] : null;
            $value['text'] = isset($value['text']) ? $value['text'] : null;
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

}
