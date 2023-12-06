<?php
require_once "classes/role.php";

$pageTitle = "Recenze k článku";
$pageContent = "pages/autor-one-article-all-reviews_cont.php";
$requiresAuthentication = true;
$requiresRole = true;
$allowedRoles = [ Role::Autor ];
include "includes/layout.php";
?>