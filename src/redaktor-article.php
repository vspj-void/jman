<?php
require_once "classes/role.php";

$pageTitle = "Moje články";
$pageContent = "pages/redaktor-article_cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Redaktor ];
include "includes/layout.php";
?>