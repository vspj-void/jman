<?php
require_once "includes/db_connect.php";
require_once "classes/session_info.php";

$mysqli = DbConnect::connect();

$messagesPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $messagesPerPage;

$loggedUser = SessionInfo::getLoggedOsoba();

if ($loggedUser && method_exists($loggedUser, 'getId')) {
    $loggedUserId = $loggedUser->getId();
} else {
    echo "Chyba: Neplatný uživatel nebo chybějící getId metoda.";
}
?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Redaktor</h1>
        <p class="lead">námitky</p>
        <hr class='my-4'>
        <!-- Tlačítko pro otevření modálního okna -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#writeMessageModal">
            Napsat zprávu
        </button>
    </div>
</div>

<div class="container mt-4">
    <?php
    $redaktorId = null;
    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $message = $_POST['message'];
        $recipientId = $_POST['recipientId']; // Přidáno pro získání ID_PRO
    
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

        $authorName = $loggedUser->getJmeno() . ' ' . $loggedUser->getPrijmeni();


        $fileContent = "Redaktor: " . $authorName . "\n" . $message;

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

    $stmt->bind_param("iis", $senderId, $recipientId, $fileNameDatabaze); // Použito recipientId pro ID_PRO

    if (!$stmt->execute()) {
        die("Chyba při vkládání dat do databáze.");
    }

    $stmt->close();
}
    ?>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Odesílatel</th>
                <th scope="col">Zobrazit zprávu</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $chatMessages = [];

            $queryChatMessages = "SELECT ID_OD, chat_cesta FROM RECENZECHAT WHERE ID_PRO = $loggedUserId";
            $resultChatMessages = $mysqli->query($queryChatMessages);

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
                    echo '</tr>';

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
                } else {
                    echo "Chyba: Soubor '$messagePath' neexistuje.";
                }
            }
            ?>
        </tbody>
    </table>
<!-- Modální okno pro napsání zprávy -->
<div class="modal fade" id="writeMessageModal" tabindex="-1" aria-labelledby="writeMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="writeMessageModalLabel">Napsat zprávu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Formulář pro napsání zprávy -->
                <form id="writeMessageForm" action="" method="POST">
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Příjemce</label>
                        <select class="form-select" name="recipient" id="recipient" required>
                            <?php
                            $queryAuthorsToSendMessage = "SELECT DISTINCT OSOBA.ID, OSOBA.JMENO, OSOBA.PRIJMENI
                                                            FROM OSOBA
                                                            WHERE OSOBA.LOGIN IS NOT NULL AND OSOBA.ID != ?";
                            $stmtAuthorsToSendMessage = $mysqli->prepare($queryAuthorsToSendMessage);

                            if ($stmtAuthorsToSendMessage) {
                                $stmtAuthorsToSendMessage->bind_param("i", $loggedUserId);
                                $stmtAuthorsToSendMessage->execute();
                                $resultAuthorsToSendMessage = $stmtAuthorsToSendMessage->get_result();

                                while ($author = $resultAuthorsToSendMessage->fetch_assoc()) {
                                    echo "<option value='{$author['ID']}'>{$author['JMENO']} {$author['PRIJMENI']}</option>";
                                }

                                $stmtAuthorsToSendMessage->close();
                            } else {
                                echo "Chyba při přípravě dotazu: " . $mysqli->error;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Zpráva</label>
                        <textarea class="form-control" name="message" id="message" rows="3" required></textarea>
                    </div>
                    <!-- Hidden input for recipient ID_PRO -->
                    <input type="hidden" name="recipientId" id="recipientId" value="">
                    <button type="submit" class="btn btn-primary">Odeslat zprávu</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Otevření modálního okna pro napsání zprávy
        $('[data-bs-target="#writeMessageModal"]').click(function () {
            $('#writeMessageModal').modal('show');
        });

        // Update hidden input value on recipient selection change
        $('#recipient').change(function () {
            var selectedRecipientId = $(this).val();
            $('#recipientId').val(selectedRecipientId);
        });
    });
</script>

</div>
