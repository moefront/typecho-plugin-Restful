# Typecho Restful 插件

[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

## API

### 文章列表

`GET /api/posts`

| 参数        | 类型   | 描述                 |      |
| ----------- | ------ | -------------------- | ---- |
| page        | int    | 当前页               | 可选 |
| pageSize    | int    | 分页数               | 可选 |
| filterType  | string | category 或 tag      | 可选 |
| filterSlug  | string | 分类名或标签名       | 可选 |
| showContent | bool   | 是否显示文章具体内容 | 可选 |

### 页面列表

`GET /api/pages`

### 分类列表

`GET /api/categories`

### 标签列表

`GET /api/tags`

### 文章/页面详情

`GET /api/post`

| 参数 | 描述          |        |
| ---- | ------------- | ------ |
| cid  | 文章/页面 ID  | 二选一 |
| slug | 文章/页面别名 | 二选一 |

### 评论列表

`GET /api/comments`

| 参数     | 描述     |        |
| -------- | -------- | ------ |
| page     | 当前页   | 可选   |
| pageSize | 分页数   | 可选   |
| cid      | 文章 ID  | 二选一 |
| slug     | 文章别名 | 二选一 |

### 发表评论

`POST /api/comment`

PS：此处`Content-Type`为`application/json`

| 参数   | 描述        |        |
| ------ | ----------- | ------ |
| cid    | 文章 ID     | 二选一 |
| slug   | 文章别名    | 二选一 |
| parent | 父级评论 ID | 可选   |
| text   | 评论内容    | 必须   |
| author | 作者        | 必须   |
| mail   | 邮箱        | 必须   |
| url    | URL         | 可选   |

### 设置项

`GET /api/settings`
