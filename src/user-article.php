<?php
require_once "classes/role.php";

$pageTitle = "Moje články";
$pageContent = "pages/user-article_cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Autor ];
include "includes/layout.php";
?>