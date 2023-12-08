<?php
require_once "includes/db_connect.php";
require_once "classes/session_info.php";

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

    $fileName = $uploadDirectory . "/contact_redaktor_" . $redaktorId . "_" . time() . ".txt";
    $fileNameDatabaze = "contact_redaktor_" . $redaktorId . "_" . time() . ".txt";

    $file = fopen($fileName, "w");

    if ($file === false) {
        die("Nelze otevřít soubor pro zápis.");
    }

    $loggedUser = SessionInfo::getLoggedOsoba();
    $authorName = $loggedUser->getJmeno() . ' ' . $loggedUser->getPrijmeni();

    $fileContent = "Recenzent: " . $authorName . "\n\n" . $message;

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
?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Recenzent</h1>
        <p class="lead">námitky</p>
        <hr class='my-4'>
    </div>
</div>

<div class="container mt-4">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Odesílatel</th>
                <th scope="col">Zobrazit zprávu</th>
                <th scope="col">Kontaktovat redaktora</th>
            </tr>
        </thead>
        <tbody>

            <?php
            $chatMessages = [];

            $loggedUser = SessionInfo::getLoggedOsoba();
            $redaktorId = $loggedUser->getId();
            
            // Přizpůsobený dotaz pro získání zpráv s ID_PRO = aktuálního uživatele
            $queryChatMessages = "SELECT ID_OD, chat_cesta FROM RECENZECHAT WHERE ID_PRO = ?";
            $stmtChatMessages = $mysqli->prepare($queryChatMessages);
            
            if ($stmtChatMessages) {
                $stmtChatMessages->bind_param("i", $redaktorId);
                $stmtChatMessages->execute();
                $resultChatMessages = $stmtChatMessages->get_result();
            
                // zbytek kódu ...
                $stmtChatMessages->close();
            } else {
                echo "Chyba při přípravě dotazu: " . $mysqli->error;
            }

            if ($resultChatMessages === false) {
                die("Chyba při dotazu na zprávy: " . $mysqli->error);
            }

            $chatMessages = [];

            if ($resultChatMessages->num_rows > 0) {
                while ($rowChatMessage = $resultChatMessages->fetch_assoc()) {
                    $senderId = $rowChatMessage['ID_OD'];
                    $messagePath = $rowChatMessage['chat_cesta'];

                    $querySender = "SELECT CONCAT(JMENO, ' ', PRIJMENI) AS ODESLATEL FROM OSOBA WHERE ID = $senderId";
                    $resultSender = $mysqli->query($querySender);

                    if ($resultSender === false) {
                        die("Chyba při dotazu na jméno odesílatele: " . $mysqli->error);
                    }

                    $senderName = ($resultSender && $resultSender->num_rows > 0) ? $resultSender->fetch_assoc()['ODESLATEL'] : "Neznámý odesílatel";

                    $chatMessages[] = [
                        'sender' => $senderName,
                        'messagePath' => $messagePath,
                    ];
                }
            }

            foreach ($chatMessages as $index => $chatMessage) {
                $sender = $chatMessage['sender'];
                $messagePath = $chatMessage['messagePath'];

                $fileDirectory = realpath(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR . "reviews");
                $filePath = $fileDirectory . DIRECTORY_SEPARATOR . $messagePath;

                if (!file_exists($fileDirectory)) {
                    echo "Chyba: Složka '$fileDirectory' neexistuje.";
                    continue;
                }

                if (file_exists($filePath)) {
                    echo '<tr>';
                    echo "<td>$sender</td>";
                    echo "<td><button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#messageModal-$index'>Zobrazit zprávu</button></td>";
                    echo "<td><button type='button' class='btn btn-success' data-bs-toggle='modal' data-bs-target='#contactModal-$index'>Kontaktovat redaktora</button></td>";
                    echo '</tr>';

                    // Modální okno pro zprávu
                    echo '<div class="modal fade" id="messageModal-' . $index . '" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">';
                    echo '<div class="modal-dialog modal-dialog-scrollable modal-lg">';
                    echo '<div class="modal-content" style="width: 80%;">';
                    echo '<div class="modal-header">';
                    echo '<h5 class="modal-title" id="messageModalLabel">Zpráva od: ' . $sender . '</h5>';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                    echo '</div>';
                    echo '<div class="modal-body" style="word-break: break-word;">';

                    $messageContent = file_get_contents($filePath);
                    $lines = explode("\n", $messageContent);
                    
                    foreach ($lines as $line) {
                        echo "<p>$line</p>";
                    }

                    echo '</div>';
                    echo '<div class="modal-footer">';
                    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                    // Modální okno pro kontaktování redaktora
                    echo '<div class="modal fade" id="contactModal-' . $index . '" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">';
                    echo '<div class="modal-dialog modal-dialog-scrollable modal-lg">';
                    echo '<div class="modal-content" style="width: 80%;">';
                    echo '<div class="modal-header">';
                    echo '<h5 class="modal-title" id="contactModalLabel">Kontaktovat redaktora: </h5>';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
                    echo '</div>';
                    echo '<div class="modal-body">';

                    // Formulář pro kontaktování redaktora
                    echo '<form id="contactForm-' . $index . '" action="" method="POST">';
                    echo '<div class="mb-3">';
                    echo '<label for="contactMessage" class="form-label">Zpráva</label>';
                    echo '<textarea class="form-control" name="message" id="contactMessage-' . $index . '" rows="3" required></textarea>';
                    echo '</div>';
                    echo '<input type="hidden" name="recipientType" value="redaktor">';
                    echo '<input type="hidden" name="recipientId" value="4"> <!-- Změněno na konstantní hodnotu 4 -->';
                    echo '<button type="submit" class="btn btn-success">Odeslat zprávu</button>';
                    echo '</form>';

                    echo '</div>';
                    echo '<div class="modal-footer">';
                    echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';

                } else {
                    echo "Chyba: Soubor '$messagePath' neexistuje.";
                }
            }
            ?>

        </tbody>
    </table>
</div>
