<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";
?>

<?php
// Vaše připojení a dotaz zůstávají stejné
$mysqli = DbConnect::connect();
$loggedUserId = SessionInfo::getLoggedOsoba()->getId();

$sql = "SELECT r.ID as ID_RECENZE, pv.ID_PRISPEVKU, pv.NAZEV, pv.CESTA, r.TERMIN 
    FROM PRISPEVEK p 
    JOIN PRISPEVEKVER pv ON p.ID = pv.ID_PRISPEVKU
    JOIN RECENZE r ON pv.ID_PRISPEVKU = r.ID_PRISPEVKU AND r.VERZE = pv.VERZE
    WHERE (p.STAV = 2 OR (p.STAV = 3 AND r.CESTA IS NULL)) AND r.ID_RECENZENTA = ? ";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $loggedUserId);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Články k recenzi</h1>
        <hr class="my-4">
    </div>
</div>

<div class="container mt-4">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col">Název</th>
                <th scope="col">Otevřít článek</th>
                <th scope="col">Termín recenze</th>
                <th scope="col">Vytvořit recenzi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= $row["NAZEV"]; ?></td>
                        <td><a href="<?= isset($row["CESTA"]) ? (UPLOAD_ARTICLES_URL . "/") . $row["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                        <td><?= date("d.m.Y", strtotime($row["TERMIN"])); ?></td>
                        <td>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReviewModal" onclick="setModalData(<?= $row['ID_PRISPEVKU'] ?>, <?= $row['ID_RECENZE'] ?>)">
                                Vytvořit recenzi
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">Žádné články k zobrazení.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modální okno pro vytvoření recenze -->
<div class="modal fade" id="createReviewModal" tabindex="-1" aria-labelledby="createReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createReviewModalLabel">Vytvořit recenzi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>(1 - nejlepší, 5 - nejhorší)</div><br>
                <!-- Formulář pro vytvoření recenze -->
                <form id="reviewForm">
                    <input type="hidden" name="idPrispevku" id="modalIdPrispevku">
                    <input type="hidden" name="idRecenze" id="modalIdRecenze">
                    <div class="mb-3">
                        <label for="ratingRelevance" class="form-label">Aktuálnost</label>
                        <select class="form-select" id="ratingRelevance" name="ratingRelevance" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3"> 
                        <label for="ratingInterest" class="form-label">Zajímavost a přínosnost</label>
                        <select class="form-select" id="ratingInterest" name="ratingInterest" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ratingOriginality" class="form-label">Originalita a odborná úroveň</label>
                        <select class="form-select" id="ratingOriginality" name='ratingOriginality' required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ratingLanguage" class="form-label">Jazyková a stylistická úroveň</label>
                        <select class="form-select" id="ratingLanguage" name="ratingLanguage" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="additionalComments" class="form-label">Přidat komentář</label>
                        <textarea class="form-control" id="additionalComments" name="additionalComments" rows="3" required></textarea>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                    <button type="button" class="btn btn-primary" onclick="submitReviewForm()">Uložit recenzi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setModalData(idPrispevku, idRecenze) {
        document.getElementById('modalIdPrispevku').value = idPrispevku;
        document.getElementById('modalIdRecenze').value = idRecenze;
    }

    function submitReviewForm() {
        if (document.getElementById("reviewForm").checkValidity()) {
            var formData = new FormData(document.getElementById("reviewForm"));

            fetch('./endpoints/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Zpracujte odpověď od serveru zde
                // Zavřete modální okno, zobrazte zprávu o úspěchu atd.
                $('#createReviewModal').modal('hide');
                location.reload(); 
            })
            .catch(error => {
                console.error('Chyba:', error);
            });
        } else {
            alert("Prosím, vyplňte všechna pole.");
        }
    }
</script>

<?php $stmt->close(); ?>
