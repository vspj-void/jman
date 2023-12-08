<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";
require_once "classes/session_info.php";
require_once "classes/review.php";
require_once "config/config.php";  
//Ukládání do databáze a do souboru
$mysqli = DbConnect::connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $redaktorId = 4; // Změněno na konstantní hodnotu 4
    $message = $_POST['message'];

    if (empty($message)) {
        die("Zpráva nesmí být prázdná.");
    }

    $uploadDirectory = realpath(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR . "reviews");

    if ($uploadDirectory === false) {
        die("Nelze získat úplnou cestu k upload adresáři.");
    }

    if (!file_exists($uploadDirectory)) {
        if (!mkdir($uploadDirectory, 0777, true)) {
            die("Nelze vytvořit složku pro ukládání recenzí.");
        }
    }

    $fileName = $uploadDirectory . "/autor_message_" . $redaktorId . "_" . time() . ".txt";
    $fileNameDatabaze = "autor_message_" . $redaktorId . "_" . time() . ".txt";

    $file = fopen($fileName, "w");

    if ($file === false) {
        die("Nelze otevřít soubor pro zápis.");
    }

    $loggedUser = SessionInfo::getLoggedOsoba();
    $authorName = $loggedUser->getJmeno() . ' ' . $loggedUser->getPrijmeni();

    // Dotaz pro získání názvu článku podle ID (doporučuji vytvořit metodu pro dotaz na název článku)
    $articleId = isset($_GET['articleId']) ? $_GET['articleId'] : null;
    $queryArticleTitle = "SELECT NAZEV FROM PRISPEVEKVER WHERE ID_PRISPEVKU = $articleId";
    $resultArticleTitle = $mysqli->query($queryArticleTitle);
    $articleTitle = ($resultArticleTitle && $resultArticleTitle->num_rows > 0) ? $resultArticleTitle->fetch_assoc()['NAZEV'] : "Neznámý název článku";

    $fileContent = "Autor: " . $authorName . "\nNázev článku: " . $articleTitle . "\n\n" . $message;

    if (fwrite($file, $fileContent) === false) {
        fclose($file);
        die("Chyba při zápisu do souboru.");
    }

    fclose($file);
    chmod($fileName, 0777);

    // Insert data into the RECENZECHAT table
    $insertQuery = "INSERT INTO RECENZECHAT (ID_OD, ID_PRO, chat_cesta) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($insertQuery);
    $senderId = $loggedUser->getId();

    if ($stmt === false) {
        die("Chyba při přípravě dotazu.");
    }

    $stmt->bind_param("iis", $senderId, $redaktorId, $fileNameDatabaze);

    if (!$stmt->execute()) {
        die("Chyba při vkládání dat do databáze.");
    }

    $stmt->close();
}

// Kod pro zobrazování recenzí
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

$articleId = isset($_GET['articleId']) ? $_GET['articleId'] : null;

if ($articleId) {
    $queryReviews = "SELECT R.*, CONCAT(O.JMENO, ' ', O.PRIJMENI) AS RECENZENT, PV.CESTA AS CESTA_CLANEK 
                    FROM RECENZE R
                    INNER JOIN OSOBA O ON R.ID_RECENZENTA = O.ID
                    INNER JOIN PRISPEVEKVER PV ON R.ID_PRISPEVKU = PV.ID_PRISPEVKU AND R.VERZE = PV.VERZE
                    WHERE R.ID_PRISPEVKU = $articleId AND R.CESTA IS NOT NULL
                    ORDER BY R.DATUM_RECENZE DESC";

    $resultReviews = $mysqli->query($queryReviews);
?>


<div class="container mt-4">
    <?php if ($resultReviews->num_rows == 0) : ?>
        <p>Ke článku dosud nebyly zpracovány žádné recenze.</p>
    <?php else : ?>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">Datum recenze</th>
                    <th scope="col">Recenzent</th>
                    <th scope="col">Odbornost</th>
                    <th scope="col">Jazyková úroveň</th>
                    <th scope="col">Originalita</th>
                    <th scope="col">Vyjádření recenzenta</th>
                    <th scope="col">Námitka k recenzi</th>
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
                        <td>
                            <!-- Tlačítko pro otevření modálního okna pro kontakt s recenzentem -->
                            <button type="button" class="btn btn-primary btn-sm" onclick="openContactModal(<?= $rowReview['ID_RECENZENTA'] ?>)">
                                Kontaktovat redaktora
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

<!-- Modální okno pro vyjádření recenzenta -->
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

<!-- Modální okno pro kontakt s redaktorem -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Námitka k recenzi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Formulář pro kontakt s redaktorem -->
                <form id="contactForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="recenzentId" id="recenzentId" value="">
                    <div class="mb-3">
                        <label for="message" class="form-label">Zadejte text pro redaktora</label>
                        <textarea class="form-control" name="message" id="message" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Odeslat</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript funkce pro otevření modálního okna s předaným textem vyjádření recenzenta
    function openModal(text) {
        // Nastavení textu modálního okna
        document.getElementById('modalText').innerHTML = text;

        // Otevření modálního okna
        var commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
        commentModal.show();
    }

// JavaScript funkce pro otevření modálního okna pro kontakt s redaktorem
function openContactModal(redaktorId) {
    // Nastavení dat redaktora pro formulář
    document.getElementById('recenzentId').value = redaktorId;

    // Otevření modálního okna
    var contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
    contactModal.show();
}

</script>
<?php } ?>
