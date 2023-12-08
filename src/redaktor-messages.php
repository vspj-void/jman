<?php
require_once "classes/role.php";

$pageTitle = "Zprávy";
$pageContent = "pages/redaktor-messages.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Redaktor ];
include "includes/layout.php";
?>