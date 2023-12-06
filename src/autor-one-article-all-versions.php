<?php
require_once "classes/role.php";

$pageTitle = "Verze článku";
$pageContent = "pages/autor-one-article-all-versions_cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Autor ];
include "includes/layout.php";
?>