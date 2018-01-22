-- Adminer 4.3.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `typecho_test_db`;
CREATE DATABASE `typecho_test_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `typecho_test_db`;

DROP TABLE IF EXISTS `typecho_comments`;
CREATE TABLE `typecho_comments` (
  `coid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned DEFAULT '0',
  `created` int(10) unsigned DEFAULT '0',
  `author` varchar(150) DEFAULT NULL,
  `authorId` int(10) unsigned DEFAULT '0',
  `ownerId` int(10) unsigned DEFAULT '0',
  `mail` varchar(150) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `agent` varchar(511) DEFAULT NULL,
  `text` text,
  `type` varchar(16) DEFAULT 'comment',
  `status` varchar(16) DEFAULT 'approved',
  `parent` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`coid`),
  KEY `cid` (`cid`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_comments` (`coid`, `cid`, `created`, `author`, `authorId`, `ownerId`, `mail`, `url`, `ip`, `agent`, `text`, `type`, `status`, `parent`) VALUES
(1,	1,	1516520053,	'Typecho',	0,	1,	NULL,	'http://typecho.org',	'127.0.0.1',	'Typecho 1.1/17.12.14',	'欢迎加入 Typecho 大家族',	'comment',	'approved',	0);

DROP TABLE IF EXISTS `typecho_contents`;
CREATE TABLE `typecho_contents` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT '0',
  `modified` int(10) unsigned DEFAULT '0',
  `text` longtext,
  `order` int(10) unsigned DEFAULT '0',
  `authorId` int(10) unsigned DEFAULT '0',
  `template` varchar(32) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'post',
  `status` varchar(16) DEFAULT 'publish',
  `password` varchar(32) DEFAULT NULL,
  `commentsNum` int(10) unsigned DEFAULT '0',
  `allowComment` char(1) DEFAULT '0',
  `allowPing` char(1) DEFAULT '0',
  `allowFeed` char(1) DEFAULT '0',
  `parent` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_contents` (`cid`, `title`, `slug`, `created`, `modified`, `text`, `order`, `authorId`, `template`, `type`, `status`, `password`, `commentsNum`, `allowComment`, `allowPing`, `allowFeed`, `parent`) VALUES
(1,	'欢迎使用 Typecho',	'start',	1516520040,	1516585449,	'<!--markdown-->如果您看到这篇文章,表示您的 blog 已经安装成功.',	0,	1,	NULL,	'post',	'publish',	NULL,	1,	'1',	'1',	'1',	0),
(2,	'关于',	'start-page',	1516520053,	1516520053,	'<!--markdown-->本页面由 Typecho 创建, 这只是个测试页面.',	0,	1,	NULL,	'page',	'publish',	NULL,	0,	'1',	'1',	'1',	0);

DROP TABLE IF EXISTS `typecho_fields`;
CREATE TABLE `typecho_fields` (
  `cid` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(8) DEFAULT 'str',
  `str_value` text,
  `int_value` int(10) DEFAULT '0',
  `float_value` float DEFAULT '0',
  PRIMARY KEY (`cid`,`name`),
  KEY `int_value` (`int_value`),
  KEY `float_value` (`float_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `typecho_metas`;
CREATE TABLE `typecho_metas` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `type` varchar(32) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `count` int(10) unsigned DEFAULT '0',
  `order` int(10) unsigned DEFAULT '0',
  `parent` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`mid`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_metas` (`mid`, `name`, `slug`, `type`, `description`, `count`, `order`, `parent`) VALUES
(1,	'默认分类',	'default',	'category',	'只是一个默认分类',	1,	1,	0),
(2,	'分类1',	'cat1',	'category',	NULL,	1,	2,	0),
(3,	'分类2',	'cat2',	'category',	NULL,	0,	3,	0),
(4,	'tag1',	'tag1',	'tag',	NULL,	1,	0,	0),
(5,	'tag2',	'tag2',	'tag',	NULL,	0,	0,	0);

DROP TABLE IF EXISTS `typecho_options`;
CREATE TABLE `typecho_options` (
  `name` varchar(32) NOT NULL,
  `user` int(10) unsigned NOT NULL DEFAULT '0',
  `value` text,
  PRIMARY KEY (`name`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_options` (`name`, `user`, `value`) VALUES
('actionTable',	0,	'a:0:{}'),
('allowRegister',	0,	'0'),
('allowXmlRpc',	0,	'2'),
('attachmentTypes',	0,	'@image@'),
('autoSave',	0,	'0'),
('charset',	0,	'UTF-8'),
('commentDateFormat',	0,	'F jS, Y \\a\\t h:i a'),
('commentsAntiSpam',	0,	'1'),
('commentsAutoClose',	0,	'0'),
('commentsAvatar',	0,	'1'),
('commentsAvatarRating',	0,	'G'),
('commentsCheckReferer',	0,	'1'),
('commentsHTMLTagAllowed',	0,	NULL),
('commentsListSize',	0,	'10'),
('commentsMarkdown',	0,	'0'),
('commentsMaxNestingLevels',	0,	'5'),
('commentsOrder',	0,	'ASC'),
('commentsPageBreak',	0,	'0'),
('commentsPageDisplay',	0,	'last'),
('commentsPageSize',	0,	'20'),
('commentsPostInterval',	0,	'60'),
('commentsPostIntervalEnable',	0,	'1'),
('commentsPostTimeout',	0,	'2592000'),
('commentsRequireMail',	0,	'1'),
('commentsRequireModeration',	0,	'0'),
('commentsRequireURL',	0,	'0'),
('commentsShowCommentOnly',	0,	'0'),
('commentsShowUrl',	0,	'1'),
('commentsThreaded',	0,	'1'),
('commentsUrlNofollow',	0,	'1'),
('commentsWhitelist',	0,	'0'),
('contentType',	0,	'text/html'),
('defaultAllowComment',	0,	'1'),
('defaultAllowFeed',	0,	'1'),
('defaultAllowPing',	0,	'1'),
('defaultCategory',	0,	'1'),
('description',	0,	'Just So So ...'),
('editorSize',	0,	'350'),
('feedFullText',	0,	'1'),
('frontArchive',	0,	'0'),
('frontPage',	0,	'recent'),
('generator',	0,	'Typecho 1.1/17.12.14'),
('gzip',	0,	'0'),
('installed',	0,	'1'),
('keywords',	0,	'typecho,php,blog'),
('lang',	0,	'zh_CN'),
('markdown',	0,	'1'),
('pageSize',	0,	'5'),
('panelTable',	0,	'a:0:{}'),
('plugin:Restful',	0,	'a:12:{s:5:\"posts\";i:1;s:5:\"pages\";i:1;s:10:\"categories\";i:1;s:4:\"tags\";i:1;s:4:\"post\";i:1;s:8:\"comments\";i:1;s:7:\"comment\";i:1;s:8:\"settings\";i:1;s:5:\"users\";i:1;s:8:\"archives\";i:1;s:6:\"origin\";N;s:8:\"csrfSalt\";s:32:\"05faabd6637f7e30c797973a558d4372\";}'),
('plugins',	0,	'a:2:{s:9:\"activated\";a:1:{s:7:\"Restful\";a:1:{s:7:\"handles\";a:1:{s:23:\"Widget_Feedback:comment\";a:1:{i:0;a:2:{i:0;s:14:\"Restful_Plugin\";i:1;s:7:\"comment\";}}}}}s:7:\"handles\";a:1:{s:23:\"Widget_Feedback:comment\";a:1:{i:0;a:2:{i:0;s:14:\"Restful_Plugin\";i:1;s:7:\"comment\";}}}}'),
('postDateFormat',	0,	'Y-m-d'),
('postsListSize',	0,	'10'),
('rewrite',	0,	'1'),
('routingTable',	0,	'a:36:{i:0;a:35:{s:5:\"index\";a:6:{s:3:\"url\";s:1:\"/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:8:\"|^[/]?$|\";s:6:\"format\";s:1:\"/\";s:6:\"params\";a:0:{}}s:7:\"archive\";a:6:{s:3:\"url\";s:6:\"/blog/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:13:\"|^/blog[/]?$|\";s:6:\"format\";s:6:\"/blog/\";s:6:\"params\";a:0:{}}s:2:\"do\";a:6:{s:3:\"url\";s:22:\"/action/[action:alpha]\";s:6:\"widget\";s:9:\"Widget_Do\";s:6:\"action\";s:6:\"action\";s:4:\"regx\";s:32:\"|^/action/([_0-9a-zA-Z-]+)[/]?$|\";s:6:\"format\";s:10:\"/action/%s\";s:6:\"params\";a:1:{i:0;s:6:\"action\";}}s:4:\"post\";a:6:{s:3:\"url\";s:24:\"/archives/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:26:\"|^/archives/([0-9]+)[/]?$|\";s:6:\"format\";s:13:\"/archives/%s/\";s:6:\"params\";a:1:{i:0;s:3:\"cid\";}}s:10:\"attachment\";a:6:{s:3:\"url\";s:26:\"/attachment/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:28:\"|^/attachment/([0-9]+)[/]?$|\";s:6:\"format\";s:15:\"/attachment/%s/\";s:6:\"params\";a:1:{i:0;s:3:\"cid\";}}s:8:\"category\";a:6:{s:3:\"url\";s:17:\"/category/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:25:\"|^/category/([^/]+)[/]?$|\";s:6:\"format\";s:13:\"/category/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:3:\"tag\";a:6:{s:3:\"url\";s:12:\"/tag/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:20:\"|^/tag/([^/]+)[/]?$|\";s:6:\"format\";s:8:\"/tag/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:6:\"author\";a:6:{s:3:\"url\";s:22:\"/author/[uid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:24:\"|^/author/([0-9]+)[/]?$|\";s:6:\"format\";s:11:\"/author/%s/\";s:6:\"params\";a:1:{i:0;s:3:\"uid\";}}s:6:\"search\";a:6:{s:3:\"url\";s:19:\"/search/[keywords]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:23:\"|^/search/([^/]+)[/]?$|\";s:6:\"format\";s:11:\"/search/%s/\";s:6:\"params\";a:1:{i:0;s:8:\"keywords\";}}s:10:\"index_page\";a:6:{s:3:\"url\";s:21:\"/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:22:\"|^/page/([0-9]+)[/]?$|\";s:6:\"format\";s:9:\"/page/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"page\";}}s:12:\"archive_page\";a:6:{s:3:\"url\";s:26:\"/blog/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:27:\"|^/blog/page/([0-9]+)[/]?$|\";s:6:\"format\";s:14:\"/blog/page/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"page\";}}s:13:\"category_page\";a:6:{s:3:\"url\";s:32:\"/category/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:34:\"|^/category/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:16:\"/category/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"slug\";i:1;s:4:\"page\";}}s:8:\"tag_page\";a:6:{s:3:\"url\";s:27:\"/tag/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:29:\"|^/tag/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:11:\"/tag/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"slug\";i:1;s:4:\"page\";}}s:11:\"author_page\";a:6:{s:3:\"url\";s:37:\"/author/[uid:digital]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:33:\"|^/author/([0-9]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:14:\"/author/%s/%s/\";s:6:\"params\";a:2:{i:0;s:3:\"uid\";i:1;s:4:\"page\";}}s:11:\"search_page\";a:6:{s:3:\"url\";s:34:\"/search/[keywords]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:32:\"|^/search/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:14:\"/search/%s/%s/\";s:6:\"params\";a:2:{i:0;s:8:\"keywords\";i:1;s:4:\"page\";}}s:12:\"archive_year\";a:6:{s:3:\"url\";s:18:\"/[year:digital:4]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:19:\"|^/([0-9]{4})[/]?$|\";s:6:\"format\";s:4:\"/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"year\";}}s:13:\"archive_month\";a:6:{s:3:\"url\";s:36:\"/[year:digital:4]/[month:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:30:\"|^/([0-9]{4})/([0-9]{2})[/]?$|\";s:6:\"format\";s:7:\"/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"year\";i:1;s:5:\"month\";}}s:11:\"archive_day\";a:6:{s:3:\"url\";s:52:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:41:\"|^/([0-9]{4})/([0-9]{2})/([0-9]{2})[/]?$|\";s:6:\"format\";s:10:\"/%s/%s/%s/\";s:6:\"params\";a:3:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:3:\"day\";}}s:17:\"archive_year_page\";a:6:{s:3:\"url\";s:38:\"/[year:digital:4]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:33:\"|^/([0-9]{4})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:12:\"/%s/page/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"year\";i:1;s:4:\"page\";}}s:18:\"archive_month_page\";a:6:{s:3:\"url\";s:56:\"/[year:digital:4]/[month:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:44:\"|^/([0-9]{4})/([0-9]{2})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:15:\"/%s/%s/page/%s/\";s:6:\"params\";a:3:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:4:\"page\";}}s:16:\"archive_day_page\";a:6:{s:3:\"url\";s:72:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:55:\"|^/([0-9]{4})/([0-9]{2})/([0-9]{2})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:18:\"/%s/%s/%s/page/%s/\";s:6:\"params\";a:4:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:3:\"day\";i:3;s:4:\"page\";}}s:12:\"comment_page\";a:6:{s:3:\"url\";s:53:\"[permalink:string]/comment-page-[commentPage:digital]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:36:\"|^(.+)/comment\\-page\\-([0-9]+)[/]?$|\";s:6:\"format\";s:18:\"%s/comment-page-%s\";s:6:\"params\";a:2:{i:0;s:9:\"permalink\";i:1;s:11:\"commentPage\";}}s:4:\"feed\";a:6:{s:3:\"url\";s:20:\"/feed[feed:string:0]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:4:\"feed\";s:4:\"regx\";s:17:\"|^/feed(.*)[/]?$|\";s:6:\"format\";s:7:\"/feed%s\";s:6:\"params\";a:1:{i:0;s:4:\"feed\";}}s:8:\"feedback\";a:6:{s:3:\"url\";s:31:\"[permalink:string]/[type:alpha]\";s:6:\"widget\";s:15:\"Widget_Feedback\";s:6:\"action\";s:6:\"action\";s:4:\"regx\";s:29:\"|^(.+)/([_0-9a-zA-Z-]+)[/]?$|\";s:6:\"format\";s:5:\"%s/%s\";s:6:\"params\";a:2:{i:0;s:9:\"permalink\";i:1;s:4:\"type\";}}s:4:\"page\";a:6:{s:3:\"url\";s:12:\"/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:22:\"|^/([^/]+)\\.html[/]?$|\";s:6:\"format\";s:8:\"/%s.html\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:10:\"rest_posts\";a:6:{s:3:\"url\";s:10:\"/api/posts\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"postsAction\";s:4:\"regx\";s:18:\"|^/api/posts[/]?$|\";s:6:\"format\";s:10:\"/api/posts\";s:6:\"params\";a:0:{}}s:10:\"rest_pages\";a:6:{s:3:\"url\";s:10:\"/api/pages\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"pagesAction\";s:4:\"regx\";s:18:\"|^/api/pages[/]?$|\";s:6:\"format\";s:10:\"/api/pages\";s:6:\"params\";a:0:{}}s:15:\"rest_categories\";a:6:{s:3:\"url\";s:15:\"/api/categories\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:16:\"categoriesAction\";s:4:\"regx\";s:23:\"|^/api/categories[/]?$|\";s:6:\"format\";s:15:\"/api/categories\";s:6:\"params\";a:0:{}}s:9:\"rest_tags\";a:6:{s:3:\"url\";s:9:\"/api/tags\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:10:\"tagsAction\";s:4:\"regx\";s:17:\"|^/api/tags[/]?$|\";s:6:\"format\";s:9:\"/api/tags\";s:6:\"params\";a:0:{}}s:9:\"rest_post\";a:6:{s:3:\"url\";s:9:\"/api/post\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:10:\"postAction\";s:4:\"regx\";s:17:\"|^/api/post[/]?$|\";s:6:\"format\";s:9:\"/api/post\";s:6:\"params\";a:0:{}}s:13:\"rest_comments\";a:6:{s:3:\"url\";s:13:\"/api/comments\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"commentsAction\";s:4:\"regx\";s:21:\"|^/api/comments[/]?$|\";s:6:\"format\";s:13:\"/api/comments\";s:6:\"params\";a:0:{}}s:12:\"rest_comment\";a:6:{s:3:\"url\";s:12:\"/api/comment\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:13:\"commentAction\";s:4:\"regx\";s:20:\"|^/api/comment[/]?$|\";s:6:\"format\";s:12:\"/api/comment\";s:6:\"params\";a:0:{}}s:13:\"rest_settings\";a:6:{s:3:\"url\";s:13:\"/api/settings\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"settingsAction\";s:4:\"regx\";s:21:\"|^/api/settings[/]?$|\";s:6:\"format\";s:13:\"/api/settings\";s:6:\"params\";a:0:{}}s:10:\"rest_users\";a:6:{s:3:\"url\";s:10:\"/api/users\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"usersAction\";s:4:\"regx\";s:18:\"|^/api/users[/]?$|\";s:6:\"format\";s:10:\"/api/users\";s:6:\"params\";a:0:{}}s:13:\"rest_archives\";a:6:{s:3:\"url\";s:13:\"/api/archives\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"archivesAction\";s:4:\"regx\";s:21:\"|^/api/archives[/]?$|\";s:6:\"format\";s:13:\"/api/archives\";s:6:\"params\";a:0:{}}}s:5:\"index\";a:3:{s:3:\"url\";s:1:\"/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:7:\"archive\";a:3:{s:3:\"url\";s:6:\"/blog/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:2:\"do\";a:3:{s:3:\"url\";s:22:\"/action/[action:alpha]\";s:6:\"widget\";s:9:\"Widget_Do\";s:6:\"action\";s:6:\"action\";}s:4:\"post\";a:3:{s:3:\"url\";s:24:\"/archives/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:10:\"attachment\";a:3:{s:3:\"url\";s:26:\"/attachment/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:8:\"category\";a:3:{s:3:\"url\";s:17:\"/category/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:3:\"tag\";a:3:{s:3:\"url\";s:12:\"/tag/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:6:\"author\";a:3:{s:3:\"url\";s:22:\"/author/[uid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:6:\"search\";a:3:{s:3:\"url\";s:19:\"/search/[keywords]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:10:\"index_page\";a:3:{s:3:\"url\";s:21:\"/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"archive_page\";a:3:{s:3:\"url\";s:26:\"/blog/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:13:\"category_page\";a:3:{s:3:\"url\";s:32:\"/category/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:8:\"tag_page\";a:3:{s:3:\"url\";s:27:\"/tag/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"author_page\";a:3:{s:3:\"url\";s:37:\"/author/[uid:digital]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"search_page\";a:3:{s:3:\"url\";s:34:\"/search/[keywords]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"archive_year\";a:3:{s:3:\"url\";s:18:\"/[year:digital:4]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:13:\"archive_month\";a:3:{s:3:\"url\";s:36:\"/[year:digital:4]/[month:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"archive_day\";a:3:{s:3:\"url\";s:52:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:17:\"archive_year_page\";a:3:{s:3:\"url\";s:38:\"/[year:digital:4]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:18:\"archive_month_page\";a:3:{s:3:\"url\";s:56:\"/[year:digital:4]/[month:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:16:\"archive_day_page\";a:3:{s:3:\"url\";s:72:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"comment_page\";a:3:{s:3:\"url\";s:53:\"[permalink:string]/comment-page-[commentPage:digital]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:4:\"feed\";a:3:{s:3:\"url\";s:20:\"/feed[feed:string:0]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:4:\"feed\";}s:8:\"feedback\";a:3:{s:3:\"url\";s:31:\"[permalink:string]/[type:alpha]\";s:6:\"widget\";s:15:\"Widget_Feedback\";s:6:\"action\";s:6:\"action\";}s:4:\"page\";a:3:{s:3:\"url\";s:12:\"/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:10:\"rest_posts\";a:3:{s:3:\"url\";s:10:\"/api/posts\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"postsAction\";}s:10:\"rest_pages\";a:3:{s:3:\"url\";s:10:\"/api/pages\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"pagesAction\";}s:15:\"rest_categories\";a:3:{s:3:\"url\";s:15:\"/api/categories\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:16:\"categoriesAction\";}s:9:\"rest_tags\";a:3:{s:3:\"url\";s:9:\"/api/tags\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:10:\"tagsAction\";}s:9:\"rest_post\";a:3:{s:3:\"url\";s:9:\"/api/post\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:10:\"postAction\";}s:13:\"rest_comments\";a:3:{s:3:\"url\";s:13:\"/api/comments\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"commentsAction\";}s:12:\"rest_comment\";a:3:{s:3:\"url\";s:12:\"/api/comment\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:13:\"commentAction\";}s:13:\"rest_settings\";a:3:{s:3:\"url\";s:13:\"/api/settings\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"settingsAction\";}s:10:\"rest_users\";a:3:{s:3:\"url\";s:10:\"/api/users\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:11:\"usersAction\";}s:13:\"rest_archives\";a:3:{s:3:\"url\";s:13:\"/api/archives\";s:6:\"widget\";s:14:\"Restful_Action\";s:6:\"action\";s:14:\"archivesAction\";}}'),
('secret',	0,	'2ejc9tw*kO^vPyY&NSs7Kt$h3dcWtnXP'),
('siteUrl',	0,	'http://localhost:2333'),
('theme',	0,	'default'),
('theme:default',	0,	'a:2:{s:7:\"logoUrl\";N;s:12:\"sidebarBlock\";a:5:{i:0;s:15:\"ShowRecentPosts\";i:1;s:18:\"ShowRecentComments\";i:2;s:12:\"ShowCategory\";i:3;s:11:\"ShowArchive\";i:4;s:9:\"ShowOther\";}}'),
('timezone',	0,	'28800'),
('title',	0,	'Hello World'),
('xmlrpcMarkdown',	0,	'0');

DROP TABLE IF EXISTS `typecho_relationships`;
CREATE TABLE `typecho_relationships` (
  `cid` int(10) unsigned NOT NULL,
  `mid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cid`,`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_relationships` (`cid`, `mid`) VALUES
(1,	1),
(1,	2),
(1,	4);

DROP TABLE IF EXISTS `typecho_users`;
CREATE TABLE `typecho_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `mail` varchar(150) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `screenName` varchar(32) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT '0',
  `activated` int(10) unsigned DEFAULT '0',
  `logged` int(10) unsigned DEFAULT '0',
  `group` varchar(16) DEFAULT 'visitor',
  `authCode` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `typecho_users` (`uid`, `name`, `password`, `mail`, `url`, `screenName`, `created`, `activated`, `logged`, `group`, `authCode`) VALUES
(1,	'admin',	'$P$BD5WcZVsOvI2QjiBDuDSNKbPzjByyx1',	'webmaster@yourdomain.com',	'http://www.typecho.org',	'admin',	1516520053,	1516585496,	1516521817,	'administrator',	'07893aeff14bc33b097f49b47c82fe2b');

-- 2018-01-22 01:45:27
