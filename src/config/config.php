<?php

// Vypisuj všechny errory do browseru
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set("display_errors", 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'halvova');
define('DB_PASS', 'Tis*9615541');
define('DB_NAME', 'halvova');
define('ROOT_DIRECTORY', dirname(dirname(__FILE__)));
define('ROOT_URL', ".");
define('UPLOAD_DIRECTORY', ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "upload");
define('UPLOAD_URL', ROOT_URL . "/upload");
define('UPLOAD_ARTICLES_DIRECTORY', UPLOAD_DIRECTORY . DIRECTORY_SEPARATOR . "articles");
define('UPLOAD_ARTICLES_URL', UPLOAD_URL . "/articles");
define('SESSION_VAR_USER_IS_LOGGED', "user_is_logged");
define('SESSION_VAR_USER_NAME', "username");
define('NOT_LOGGED_REDIRECT_URL', "./index.php");
define('SESSION_VAR_USER_ROLE', 'user_role');
