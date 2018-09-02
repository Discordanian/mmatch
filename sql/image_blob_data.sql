CREATE TABLE IF NOT EXISTS `image_blob_data` (
  `image_blob_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique, auto generated blob identifier (primary key)',
  `image_type` enum ('org_logo') NOT NULL COMMENT 'Type of image stored, also references to which table the key_id refers',
  `key_id` int(10) unsigned NOT NULL COMMENT 'Unique ID of of the record tied to this image',
  `image_blob` mediumblob NOT NULL COMMENT 'BLOB of stored image data',
  `image_mime_type` enum ('image/png', 'image/jpeg', 'image/gif', 'image/bmp') NOT NULL COMMENT 'Mime type of stored image',
  `file_size` int(10) unsigned NOT NULL COMMENT 'Size of stored image data',
  `file_name` varchar(128) NOT NULL COMMENT 'Name of file supplied by user',
  `update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_blob_id`),
  UNIQUE KEY `ix_image_blob_data_type_unique` (`image_type`, `key_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Stored image data';
