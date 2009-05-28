CREATE TABLE `prefix_pma_designer_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `x` INT,
  `y` INT,
  `v` TINYINT,
  `h` TINYINT,
  PRIMARY KEY (`db_name`,`table_name`)
)  ENGINE=MyISAM COMMENT='Table coordinates for Designer' DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
