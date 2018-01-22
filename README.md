# Typecho Restful 插件

[![Build Status](https://travis-ci.org/moefront/typecho-plugin-Restful.svg?branch=master)](https://travis-ci.org/moefront/typecho-plugin-Restful)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)
![built by](https://img.shields.io/badge/built_by-MoeFront-ff69b4.svg)

## API

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

### 发表评论

`POST /api/comment`

PS：此处`Content-Type`为`application/json`

| 参数   | 类型   | 描述                      |        |
| ------ | ------ | ------------------------- | ------ |
| cid    | int    | 文章 ID                   | 二选一 |
| slug   | string | 文章别名                  | 二选一 |
| parent | int    | 父级评论 ID               | 可选   |
| text   | string | 评论内容                  | 必须   |
| author | string | 作者                      | 必须   |
| mail   | string | 邮箱                      | 必须   |
| url    | string | URL                       | 可选   |
| token  | string | 文章/页面详情返回的 token  | 必须   |

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
| showContent  | boolean | 是否显示文章内容            | 可选 |
| order        | string  | 归档的排序方式 (asc / desc) | 可选 |
