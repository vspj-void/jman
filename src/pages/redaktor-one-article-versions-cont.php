<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    // var_dump($articleFile);
}

$mysqli = DbConnect::connect();

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Redaktor</h1>
        <p class="lead">Verze vybraného článku</p>
        <hr class="my-4">
    </div>
</div>
<br/>

<?php
if (isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];

// Dotaz pro získání článků s možností řazení
$queryArticles = "SELECT PV.*, O.JMENO as AUTOR_JMENO, O.PRIJMENI as AUTOR_PRIJMENI, C.TEMA as CASOPIS_TEMA, PV.VERZE as VERZE
                  FROM PRISPEVEKVER PV
                  INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                  INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                  INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
                  INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
                  WHERE PV.ID_PRISPEVKU = $articleId
                  ORDER BY PV.VERZE DESC";


$resultArticles = $mysqli->query($queryArticles);

?>


<div class="container mt-4">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col">Název</th>
                <th scope="col">Verze</th>
                <th scope="col">Autor</th>
                <th scope="col">Otevřít článek</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                <tr>
                    <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                    <td><?= !empty($rowArticle["VERZE"]) ? $rowArticle["VERZE"] : "Název není k dispozici"; ?></td>
                    <td><?= isset($rowArticle["AUTOR_JMENO"]) && isset($rowArticle["AUTOR_PRIJMENI"]) ? $rowArticle["AUTOR_JMENO"] . " " . $rowArticle["AUTOR_PRIJMENI"] : "Autor není k dispozici"; ?></td>
                    <td><a href="<?= isset($rowArticle["CESTA"]) ? (UPLOAD_ARTICLES_URL . "/") . $rowArticle["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
} // ¯\_(ツ)_/¯
?>

