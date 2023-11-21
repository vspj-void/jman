<?php

require_once "config/config.php";
require_once "classes/session_info.php";

session_start();
if ($requiresAuthentication) {
    if (!SessionInfo::isUserLogged()) {
        // User not authorized
        // Redirect to default page
        echo "<script>alert('Pro přístup na stránku se vyžaduje přihlášení.'); window.location.replace('./index.php');</script>";
        exit;
    }

    if ($requiresRole && !in_array(SessionInfo::getLoggedOsoba()->getProfil()->getrole(), $allowedRoles)) {
        // User not authorized
        // Redirect to default page
        echo "<script>alert('Pro přístup na stránku musíte mít požadovanou roli.'); window.location.replace('./index.php');</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">

    <title><?php echo "Křídlo | $pageTitle"; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php include "includes/header.php"; ?>

    <div class="container-fluid p-5">
        <?php require $pageContent; // Promenna s nazvem souboru s obsahem stranky, typicky: "pages/nazevstranky-content.php" 
        ?>
    </div>

    <?php include "includes/footer.php"; ?>
</body>

</html>