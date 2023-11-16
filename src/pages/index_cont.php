<?php

require_once "includes/db_connect.php";
require_once "classes/profil.php";

$query = "SELECT * FROM OSOBA WHERE login IS NOT NULL";
$result = $mysqli->query($query);

// echo $result->num_rows;
// var_dump(Profil::getLoggedOsoba());

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    var_dump($articleFile);
}

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Vítejte na webu časopisu Křídlo</h1>
        <p class="lead">Píšeme pro Vás</p>
        <hr class="my-4">
        <p>Více informací o nás.</p>
        <a class="btn btn-primary btn-lg" href="#" role="button">Zjistit více</a>
    </div>
</div>