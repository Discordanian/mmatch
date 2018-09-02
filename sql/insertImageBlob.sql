DROP PROCEDURE IF EXISTS insertImageBlob;

DELIMITER $$
CREATE PROCEDURE insertImageBlob(p_image_type varchar(10), p_key_id INT unsigned, p_image_blob mediumblob,
	p_mime_type varchar(10), p_file_size INT unsigned, p_file_name VARCHAR(128))

BEGIN

	/* 	 only store 1 image for each type, so just delete 
	this is only called when a new image is uploaded,
	so it should not be a big deal to just delete the old one every time */
	DELETE FROM image_blob_data WHERE image_type = p_image_type AND key_id = p_key_id;

	INSERT INTO image_blob_data (image_type, key_id, image_blob, image_mime_type, file_size, file_name) 
	VALUES (p_image_type, p_key_id, p_image_blob, p_mime_type, p_file_size, p_file_name);
END
$$
DELIMITER ;

GRANT EXECUTE ON PROCEDURE insertImageBlob TO movemusr@localhost;
