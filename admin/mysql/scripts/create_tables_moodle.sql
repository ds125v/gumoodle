CREATE TABLE `prefix_pma_bookmark` (
  `id` int(11) NOT NULL auto_increment,
  `dbase` varchar(255) NOT NULL default '',
  `user` varchar(255) NOT NULL default '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
)  ENGINE=MyISAM COMMENT='Bookmarks'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_column_info` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `column_name` varchar(64) NOT NULL default '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `transformation` varchar(255) NOT NULL default '',
  `transformation_options` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
)  ENGINE=MyISAM COMMENT='Column information for phpMyAdmin'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_history` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `db` varchar(64) NOT NULL default '',
  `table` varchar(64) NOT NULL default '',
  `timevalue` timestamp(14) NOT NULL,
  `sqlquery` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`,`db`,`table`,`timevalue`)
)  ENGINE=MyISAM COMMENT='SQL history for phpMyAdmin'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_pdf_pages` (
  `db_name` varchar(64) NOT NULL default '',
  `page_nr` int(10) unsigned NOT NULL auto_increment,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  PRIMARY KEY  (`page_nr`),
  KEY `db_name` (`db_name`)
)  ENGINE=MyISAM COMMENT='PDF relation pages for phpMyAdmin'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

CREATE TABLE `prefix_pma_relation` (
  `master_db` varchar(64) NOT NULL default '',
  `master_table` varchar(64) NOT NULL default '',
  `master_field` varchar(64) NOT NULL default '',
  `foreign_db` varchar(64) NOT NULL default '',
  `foreign_table` varchar(64) NOT NULL default '',
  `foreign_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`master_db`,`master_table`,`master_field`),
  KEY `foreign_field` (`foreign_db`,`foreign_table`)
)  ENGINE=MyISAM COMMENT='Relation table'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_table_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `pdf_page_number` int(11) NOT NULL default '0',
  `x` float unsigned NOT NULL default '0',
  `y` float unsigned NOT NULL default '0',
  PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)
)  ENGINE=MyISAM COMMENT='Table coordinates for phpMyAdmin PDF output'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_table_info` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `display_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`db_name`,`table_name`)
)  ENGINE=MyISAM COMMENT='Table information for phpMyAdmin'  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;


CREATE TABLE `prefix_pma_designer_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `x` INT,
  `y` INT,
  `v` TINYINT,
  `h` TINYINT,
  PRIMARY KEY (`db_name`,`table_name`)
)  ENGINE=MyISAM COMMENT='Table coordinates for Designer' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
