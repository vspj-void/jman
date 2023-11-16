<?php

require_once dirname(dirname(__FILE__)) . "/config/config.php";

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check the connection
if ($mysqli->connect_error) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// Set the character set to utf8
$mysqli->set_charset("utf8");
?>