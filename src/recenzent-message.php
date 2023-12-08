<?php
require_once "classes/role.php";

$pageTitle = "Zprávy";
$pageContent = "pages/recenzent-messages.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Recenzent ];
include "includes/layout.php";
?>