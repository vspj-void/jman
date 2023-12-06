<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";

require_once "classes/session_info.php";

$mysqli = DbConnect::connect();

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Autor</h1>
        <p class="lead">Verze článku</p>
        <hr class="my-4">
    </div>
</div>
<br />

<?php
if (isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];

    $queryArticles = "
SELECT
    PV.*,
    GROUP_CONCAT(CONCAT(O.JMENO, ' ', O.PRIJMENI) SEPARATOR ', ') AS AUTORSKY_TYM,
    C.TEMA AS CASOPIS_TEMA,
    PV.VERZE AS VERZE
FROM
    PRISPEVEKVER PV
    INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
    INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
    INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
    INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
WHERE
    PV.ID_PRISPEVKU = $articleId
GROUP BY
    P.ID,
    PV.VERZE
ORDER BY
    PV.VERZE DESC";

    $resultArticles = $mysqli->query($queryArticles);
?>
    <div class="container mt-4">
        <?php if ($resultArticles->num_rows == 0) :
        ?>
            <p>Požadovaný článek nebyl nalezen.</p>
        <?php
        else :
        ?>

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
                            <td><?= isset($rowArticle["AUTORSKY_TYM"]) && isset($rowArticle["AUTORSKY_TYM"]) ? $rowArticle["AUTORSKY_TYM"] : "Autor není k dispozici"; ?></td>
                            <td><a href="<?= isset($rowArticle["CESTA"]) ? (UPLOAD_ARTICLES_URL . "/") . $rowArticle["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
}
?>