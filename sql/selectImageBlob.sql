DROP PROCEDURE IF EXISTS selectImageBlob;

DELIMITER $$
CREATE PROCEDURE selectImageBlob(p_image_type VARCHAR(10), p_key_id INT UNSIGNED)
BEGIN
    SELECT image_blob, image_mime_type FROM image_blob_data 
    WHERE image_type = p_image_type AND key_id = p_key_id;
END
$$

DELIMITER ;

GRANT EXECUTE ON PROCEDURE selectImageBlob TO movemusr@localhost;
