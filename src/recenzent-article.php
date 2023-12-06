<?php
require_once "classes/role.php";

$pageTitle = "Recenze";
$pageContent = "pages/recenzent-article_cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Recenzent ];
include "includes/layout.php";
?>