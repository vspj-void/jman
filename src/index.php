<?php
require_once "classes/role.php";

$pageTitle = "Domovská stránka";
$pageContent = "pages/index_cont.php";
$requiresAuthentication = false;
$requiresRole = false;
$allowedRoles = [];
include "includes/layout.php";
