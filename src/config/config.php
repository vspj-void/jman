<?php

// Vypisuj všechny errory do browseru
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set("display_errors", 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'halvova');
define('DB_PASS', 'Tis*9615541');
define('DB_NAME', 'halvova');
define('ROOT_DIRECTORY', dirname(dirname(__FILE__)));
define('UPLOAD_DIRECTORY', ROOT_DIRECTORY . "/upload");
