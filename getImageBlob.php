<?php

require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');

$id  = filter_var($_GET["id"], FILTER_VALIDATE_INT);

initializeDb();

$stmt = $dbh->prepare("CALL selectImageBlob(?, ?); ");


$stmt->bindValue(1, 'org_logo', PDO::PARAM_STR);
$stmt->bindValue(2, $id, PDO::PARAM_INT);

$result = $stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$mime_type = $row["image_mime_type"];

header("Content-Type: $mime_type");
//header("Content-Disposition: filename=mueller.png");

echo $row["image_blob"];

