<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";

$articlesPerPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articlesPerPage;

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    // var_dump($articleFile);
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

<?php
$queryArticles = "SELECT PV.*, O.JMENO as AUTOR_JMENO, O.PRIJMENI as AUTOR_PRIJMENI, C.TEMA as CASOPIS_TEMA 
                  FROM PRISPEVEKVER PV
                  INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                  INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                  INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
                  INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
                  WHERE P.STAV = 1
                  LIMIT $offset, $articlesPerPage";
$resultArticles = $mysqli->query($queryArticles);

?>

<div class="container mt-4">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col">Název</th>
                <th scope="col">Autor</th>
                <th scope="col">Téma časopisu</th>
                <th scope="col">Otevřít článek</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                <tr>
                    <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                    <td><?= isset($rowArticle["AUTOR_JMENO"]) && isset($rowArticle["AUTOR_PRIJMENI"]) ? $rowArticle["AUTOR_JMENO"] . " " . $rowArticle["AUTOR_PRIJMENI"] : "Autor není k dispozici"; ?></td>
                    <td><?= !empty($rowArticle["CASOPIS_TEMA"]) ? $rowArticle["CASOPIS_TEMA"] : "Téma není k dispozici"; ?></td>
                    <td><a href="<?= isset($rowArticle["CESTA"]) ? "upload/" . $rowArticle["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="container">
    <ul class="pagination justify-content-center">
        <?php
        $totalArticlesQuery = "SELECT COUNT(*) as total FROM PRISPEVEKVER PV
                               INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                               INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                               WHERE P.STAV = 1";
        $totalArticlesResult = $mysqli->query($totalArticlesQuery);
        $totalArticles = $totalArticlesResult->fetch_assoc()['total'];
        $totalPages = ceil($totalArticles / $articlesPerPage);

        for ($i = 1; $i <= $totalPages; $i++) :
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</div>
