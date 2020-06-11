SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `nyarukologin`
--

-- --------------------------------------------------------

--
-- 表的结构 `s1_ban`
--

CREATE TABLE `s1_ban` (
  `id` int UNSIGNED NOT NULL COMMENT '序号',
  `fuser` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '操作人',
  `tuser` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '被操作人',
  `timeout` datetime DEFAULT NULL COMMENT '有效期至'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='屏蔽表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_comment`
--

CREATE TABLE `s1_comment` (
  `id` bigint UNSIGNED NOT NULL COMMENT '序号',
  `comment` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '评论唯一哈希',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '发布用户哈希',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发表日期',
  `modified` datetime DEFAULT NULL COMMENT '修改日期',
  `citetype` enum('POST','COMM','COMM2') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'POST' COMMENT '被评论的内容类型',
  `post` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '被评论的贴文或评论',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '评论内容',
  `type` enum('TEXT','IMAGE') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'TEXT' COMMENT '评论类型',
  `files` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '附件路径',
  `likenum` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞数',
  `likemax` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最大点赞数',
  `storey` int UNSIGNED NOT NULL COMMENT '评论层数',
  `commentnum` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论数',
  `commentmax` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最大评论数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='择择评论表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_follow`
--

CREATE TABLE `s1_follow` (
  `id` int UNSIGNED NOT NULL COMMENT '序号',
  `fuser` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '操作人',
  `tuser` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '被操作人',
  `friend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为双向',
  `timeout` datetime DEFAULT NULL COMMENT '有效期至'
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='关注表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_info`
--

CREATE TABLE `s1_info` (
  `id` int UNSIGNED NOT NULL COMMENT '序号',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希',
  `following` int UNSIGNED NOT NULL COMMENT '关注数',
  `followers` int UNSIGNED NOT NULL COMMENT '粉丝数',
  `postnum` int UNSIGNED NOT NULL COMMENT '发帖数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- 表的结构 `s1_keyword`
--

CREATE TABLE `s1_keyword` (
  `id` bigint NOT NULL COMMENT '序号',
  `hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '关键词哈希',
  `word` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '关键词描述',
  `topost` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '目标贴文哈希',
  `isai` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为后台词汇'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='择择主题表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_like`
--

CREATE TABLE `s1_like` (
  `id` bigint UNSIGNED NOT NULL COMMENT '序号',
  `user` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '发布用户哈希',
  `citetype` enum('POST','COMM') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'POST' COMMENT '被赞的内容类型',
  `post` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '被赞的贴文或评论'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='择择点赞表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_posts`
--

CREATE TABLE `s1_posts` (
  `id` bigint UNSIGNED NOT NULL COMMENT '序号',
  `post` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '贴文哈希值',
  `userhash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT '用户哈希值',
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发表日期',
  `modified` datetime DEFAULT NULL COMMENT '修改日期',
  `title` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '文章标题',
  `type` enum('POST','TEXT','IMAGE','VIDEO','VOTE') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'TEXT' COMMENT '贴文类型',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT '正文',
  `tag` text COLLATE utf8mb4_unicode_520_ci COMMENT '标签',
  `files` text CHARACTER SET ascii COLLATE ascii_bin COMMENT '附件路径',
  `share` enum('PUBLIC','CIRCLE','GROUP','USER','PRIVACY') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'PUBLIC' COMMENT '分享范围',
  `mention` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT '提及的人员哈希',
  `nocomment` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否允许评论0是1否2被（系统）封禁',
  `noforward` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否允许转发0是1否2被（系统）封禁',
  `cite` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '引用其他贴文哈希',
  `forwardnum` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '转发数',
  `forwardmax` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最大转发数',
  `commentnum` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论数',
  `commentmax` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最大评论数',
  `likenum` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '点赞数',
  `likemax` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '最大点赞数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='择择贴文表';

-- --------------------------------------------------------

--
-- 表的结构 `s1_tag`
--

CREATE TABLE `s1_tag` (
  `id` int UNSIGNED NOT NULL COMMENT 'TAGID',
  `tag` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'TAG内容',
  `stat` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态0正1隐2封',
  `hot` int UNSIGNED NOT NULL DEFAULT '1' COMMENT '当前热度',
  `hotmax` int UNSIGNED NOT NULL DEFAULT '1' COMMENT '最大热度',
  `ctime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='择择标签表';

--
-- 转储表的索引
--

--
-- 表的索引 `s1_ban`
--
ALTER TABLE `s1_ban`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_comment`
--
ALTER TABLE `s1_comment`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_follow`
--
ALTER TABLE `s1_follow`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_info`
--
ALTER TABLE `s1_info`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_like`
--
ALTER TABLE `s1_like`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_posts`
--
ALTER TABLE `s1_posts`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `s1_tag`
--
ALTER TABLE `s1_tag`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `s1_ban`
--
ALTER TABLE `s1_ban`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_comment`
--
ALTER TABLE `s1_comment`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_follow`
--
ALTER TABLE `s1_follow`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_info`
--
ALTER TABLE `s1_info`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_like`
--
ALTER TABLE `s1_like`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_posts`
--
ALTER TABLE `s1_posts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '序号';

--
-- 使用表AUTO_INCREMENT `s1_tag`
--
ALTER TABLE `s1_tag`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'TAGID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

