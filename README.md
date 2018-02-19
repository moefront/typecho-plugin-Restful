# Typecho Restful 插件

[![Build Status](https://travis-ci.org/moefront/typecho-plugin-Restful.svg?branch=master)](https://travis-ci.org/moefront/typecho-plugin-Restful)
[![Version](https://badge.fury.io/ph/moefront%2Ftypecho-plugin-restful.svg)](https://packagist.org/packages/moefront/typecho-plugin-restful)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)
![built by](https://img.shields.io/badge/built_by-MoeFront-ff69b4.svg)

这是一个将 Typecho 博客 RESTful 化的插件。启用此插件，你可以通过请求 API 向站点请求或写入信息（获取文章内容、获取评论、添加评论等）。

------

## 食用方法

### 常规

下载插件并解压，将解压后的目录重命名为 `Restful` (区分大小写)，然后到后台插件管理页面启用并设置即可。

### 使用 Composer 安装

```bash
cd /path/to/typecho/usr/plugins
composer create-project moefront/typecho-plugin-restful Restful --prefer-dist --stability=dev
chown www:www -R Restful
```

## API

下面假设您的站点已经开启了地址重写（伪静态）；如果没有的话，那么需要在下文列出的请求的 URI 前加上 `/index.php`，例如：`/api/posts` => `/index.php/api/posts`.

### 文章列表

`GET /api/posts`

| 参数        | 类型   | 描述                       |      |
| ----------- | ------ | -------------------------- | ---- |
| page        | int    | 当前页                     | 可选 |
| pageSize    | int    | 分页数                     | 可选 |
| filterType  | string | category 或 tag 或 search  | 可选 |
| filterSlug  | string | 分类名或标签名或搜索关键字 | 可选 |
| showContent | bool   | 是否显示文章具体内容       | 可选 |

### 页面列表

`GET /api/pages`

### 分类列表

`GET /api/categories`

### 标签列表

`GET /api/tags`

### 文章/页面详情

`GET /api/post`

| 参数 | 类型   | 描述          |        |
| ---- | ------ | ------------- | ------ |
| cid  | int    | 文章/页面 ID  | 二选一 |
| slug | string | 文章/页面别名 | 二选一 |

### 评论列表

`GET /api/comments`

| 参数     | 类型   | 描述                   |        |
| -------- | ------ | ---------------------- | ------ |
| page     | int    | 当前页                 | 可选   |
| pageSize | int    | 分页数                 | 可选   |
| order    | string | 评论显示顺序(asc/desc) | 可选   |
| cid      | int    | 文章 ID                | 二选一 |
| slug     | string | 文章别名               | 二选一 |

PS: 如果带上 Cookie 请求，会显示当前 Cookie 记住的用户所发布的待审核的评论。

### 发表评论

`POST /api/comment`

| 参数     | 类型    | 描述                           |        |
| -------- | ------ | ------------------------------ | ------ |
| cid      | int    | 文章 ID                         | 二选一 |
| slug     | string | 文章别名                        | 二选一 |
| parent   | int    | 父级评论 ID                     | 可选   |
| text     | string | 评论内容                        | 必须   |
| author   | string | 作者                            | 必须   |
| mail     | string | 邮箱                            | 必须   |
| url      | string | URL                            | 可选   |
| token    | string | 文章/页面详情返回的 token        | 必须   |
| uid      | int    | 已注册用户评论时，用户的 UID      | 可选   |
| authCode | string | 已注册用户评论时，用户的 authCode | 可选   |

PS：此处`Content-Type`为`application/json`, 也就是说你应当以 JSON 格式提交数据。

PS2: uid 和 authCode 可以在 Cookie 中找到（形如 `hash__typecho_uid` 和 `hash__typecho_authCode` 的内容）。如果直接带上 Cookie 请求此 API 则不再需要带上 `uid` 和 `authCode` 参数。请求时需要带上合法的 User-Agent.

### 设置项

`GET /api/settings`

### 用户信息

`GET /api/users`

| 参数 | 类型    | 描述              |     |
| ---- | ------ | ----------------- | --- |
| uid  | int    | 用户 ID           | 可选 |
| name | string | 用户的用户名或昵称 | 可选 |

### 归档

`GET /api/archives`

PS：默认按从新到旧 (desc) 顺序排列文章。

| 参数         | 类型     | 描述                       |     |
| ------------ | ------- | -------------------------- | --- |
| showContent  | bool    | 是否显示文章内容            | 可选 |
| order        | string  | 归档的排序方式 (asc / desc) | 可选 |

## 其它

### 自定义 URI 前缀

默认情况下 Restful 插件会占用 `/api/*` 用于不同的接口。如果该 URI 有其它用途，或与其它插件冲突，或者由于某些不可描述的原因用户不希望暴露该接口，可以选择通过修改 `config.inc.php` 自定义前缀。

例如，在 `config.inc.php` 文件中加入下列内容：

```php
define('__TYPECHO_RESTFUL_PREFIX__', '/rest/');
```

**重新启用插件**，此时你可以通过 `/rest/*` 访问相关 API.

## License

`typecho-plugin-restful` is MIT licensed.

Since it is a derivative of Typecho which is GPLv2 licensed, you may also need to observe GPLv2 when you are redistributing this plugin.
