<?php
require_once "classes/role.php";

$pageTitle = "Verze článku";
$pageContent = "pages/redaktor-one-article-versions-cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Redaktor ];
include "includes/layout.php";
?>