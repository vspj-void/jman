<?php
require_once "../config/config.php";

// Spustit session
session_start();

// Zničit všechny session proměnné
$_SESSION = array();

// Zničit samotnou session
session_destroy();

// Přesměrovat uživatele na hlavní stránku
header("location: ../index.php");
exit;
?>