<?php
require_once "classes/role.php";

$pageTitle = "Zprávy";
$pageContent = "pages/autor-messages.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Autor ];
include "includes/layout.php";
?>