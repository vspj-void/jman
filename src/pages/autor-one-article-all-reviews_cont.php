<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";
require_once "classes/session_info.php";
require_once "classes/review.php";

function mapToBootstrapColor($value)
{
    switch ($value) {
        case 1:
            return "success";
        case 2:
            return "info";
        case 3:
            return "warning";
        case 4:
            return "danger";
        default:
            return "dark";
    }
}

$mysqli = DbConnect::connect();

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Autor</h1>
        <p class="lead">Recenze k článku</p>
        <hr class="my-4">
    </div>
</div>
<br />

<?php
if (isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];

    $queryReviews = "
    SELECT R.*, CONCAT(O.JMENO, ' ', O.PRIJMENI) AS RECENZENT, PV.CESTA AS CESTA_CLANEK 
    FROM RECENZE R
    INNER JOIN OSOBA O ON R.ID_RECENZENTA = O.ID
    INNER JOIN PRISPEVEKVER PV ON R.ID_PRISPEVKU = PV.ID_PRISPEVKU AND R.VERZE = PV.VERZE
    WHERE R.ID_PRISPEVKU = $articleId
    ORDER BY R.DATUM_RECENZE DESC";

    $resultReviews = $mysqli->query($queryReviews);
?>
    <div class="container mt-4">
        <?php if ($resultReviews->num_rows == 0) :
        ?>
            <p>Ke článku dosud nebyly zpracovány žádné recenze.</p>
        <?php
        else :
        ?>

            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col">Datum recenze</th>
                        <th scope="col">Recenzent</th>
                        <th scope="col">Odbornost</th>
                        <th scope="col">Jazyková úroveň</th>
                        <th scope="col">Originalita</th>
                        <th scope="col">Vyjádření recenzenta</th>
                        <th scope="col">Verze článku</th>
                        <th scope="col">Původní článek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rowReview = $resultReviews->fetch_assoc()) :
                        $reviewComment = Review::getReviewContents($rowReview["CESTA"]) ?? "Soubor s vyjádřením není k dispozici.";
                        $reviewCommentHtml = nl2br($reviewComment);
                    ?>

                        <tr>
                            <td><?= (new DateTime($rowReview['DATUM_RECENZE']))->format('d.m.Y') ?></td>
                            <td><?= $rowReview['RECENZENT'] ?></td>
                            <td><span class="badge bg-<?= mapToBootstrapColor($rowReview['ODBORNOST']) ?>"><?= $rowReview['ODBORNOST'] ?></span></td>
                            <td><span class="badge bg-<?= mapToBootstrapColor($rowReview['JAZYKOVA_UROVEN']) ?>"><?= $rowReview['JAZYKOVA_UROVEN'] ?></span></td>
                            <td><span class="badge bg-<?= mapToBootstrapColor($rowReview['ORIGINALITA']) ?>"><?= $rowReview['ORIGINALITA'] ?></span></td>
                            <td>
                                <!-- Tlačítko pro otevření modálního okna -->
                                <button type="button" class="btn btn-info btn-sm" onclick="openModal(`<?= $reviewCommentHtml ?>`)">
                                    Zobrazit vyjádření
                                </button>
                            </td>
                            <td><?= $rowReview['VERZE'] ?></td>
                            <td>
                                <a href="<?= (UPLOAD_ARTICLES_URL . "/") . $rowReview["CESTA_CLANEK"] ?>" class="btn btn-info btn-sm">Zobrazit článek</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <!-- Modální okno -->
    <div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commentModalLabel">Vyjádření recenzenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Element pro vložení textu vyjádření recenzenta -->
                    <p id="modalText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript funkce pro otevření modálního okna s předaným textem
        function openModal(text) {
            // Nastavení textu modálního okna
            document.getElementById('modalText').innerHTML = text;

            // Otevření modálního okna
            var commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
            commentModal.show();
        }
    </script>
<?php
}
?>