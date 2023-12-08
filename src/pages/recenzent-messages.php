<?php

require_once "includes/db_connect.php";
require_once "classes/session_info.php";

$mysqli = DbConnect::connect();

$messagesPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $messagesPerPage;

?>

<?php
// Dotaz pro získání zpráv
$loggedUser = SessionInfo::getLoggedOsoba();
$komu = $loggedUser->getId();
$queryMessages = "SELECT O.JMENO, O.PRIJMENI, Z.PREDMET, Z.TEXT, Z.PRECTENO, Z.ID
                  FROM ZPRAVY Z JOIN OSOBA O ON Z.OD=O.ID
                  WHERE  KOMU =" . $komu . " ORDER BY PRECTENO, ID";

$resultMessages = $mysqli->query($queryMessages);

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Recenzent</h1>
        <p class="lead">Zprávy</p>
        <hr class="my-4">
    </div>
</div>

<div class="container mt-4">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <!-- Odkazy na řazení -->
                <th scope="col">Odesílatel</th>
                <th scope="col">Předmět</th>
                <th scope="col">Zpráva</th>
                <th scope="col">Přečteno</th>
            </tr>
        </thead>
        <tbody>
            <?php $sortBy = 'ID'; // defaultní řazení podle Názvu
                while ($rowMessage = $resultMessages->fetch_assoc()) : ?>
                <tr>
                    <td><?= isset($rowMessage["JMENO"]) && isset($rowMessage["PRIJMENI"]) ? $rowMessage["JMENO"] . " " . $rowMessage["PRIJMENI"] : "Autor není k dispozici"; ?></td>
                    <td><?= isset($rowMessage["PREDMET"]) ? $rowMessage["PREDMET"] : "Předmět není k dispozici"; ?></td>
                    <td><?= !empty($rowMessage["TEXT"]) ? $rowMessage["TEXT"] : "Text zprávy není k dispozici"; ?></td>
                    <td>
                    <input type="checkbox" class="message-read-checkbox" data-message-id="<?= $rowMessage['ID'] ?>" <?= $rowMessage['PRECTENO'] ? 'checked' : '' ?>>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.message-read-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        var messageId = this.getAttribute('data-message-id');
        var precteno = this.checked ? 1 : 0;

        // AJAX požadavek pro aktualizaci stavu PRECTENO
        var xhr = new XMLHttpRequest();
        xhr.open('POST', './endpoints/update_message_status.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send('id=' + messageId + '&precteno=' + precteno);

        xhr.onload = function() {
            if (this.status == 200) {
                console.log('Stav PRECTENO aktualizován');
            } else {
                console.error('Chyba při aktualizaci stavu PRECTENO');
            }
        };
    });
});
</script>

