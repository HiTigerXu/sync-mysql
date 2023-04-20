CREATE TABLE `t_sync_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sync_where` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sync_state` tinyint(4) NOT NULL DEFAULT '0' COMMENT '同步状态 0未同步 1同步中 2已同步',
  `sync_date` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '同步日期',
  `sync_time` decimal(10,2) DEFAULT NULL,
  `weight` tinyint(4) NOT NULL DEFAULT '0' COMMENT '权重 越大越优先',
  `update_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_table_name` (`table_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='同步表'

-- insert into t_sync_tables (table_name, sync_where) values ('t_xx', 'WHERE ID=xx');
