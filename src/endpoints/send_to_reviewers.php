<?php
// Zaslání článku recenzentům redaktorem

require_once "../includes/db_connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recordId = $_POST['recordId']; // ID článku
    $version = $_POST['version']; // Verze
    $reviewer1Id = $_POST['reviewer1']; // ID prvního recenzenta
    $reviewer2Id = $_POST['reviewer2']; // ID druhého recenzenta
    $reviewDate = $_POST['reviewDate']; // jak s tím pracovat v enpointu?
    $loggedUserID = $_POST['userID'];

    // Kontrola, zda nebyli vybráni stejní recenzenti
    if ($reviewer1Id == $reviewer2Id) {
        echo "Chyba: Nelze vybrat stejného recenzenta dvakrát.";
        exit; // Ukončení skriptu
    }

    // SQL dotaz pro vložení záznamů
    $query = "INSERT INTO RECENZE (TERMIN, ID_RECENZENTA, ID_PRISPEVKU, VERZE) VALUES (?, ?, ?, ?)";

    $mysqli = DbConnect::connect();
    // Vložení záznamu pro prvního recenzenta
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("siii",$reviewDate, $reviewer1Id, $recordId, $version);
        $stmt->execute();
        $stmt->close();
    }

    // Vložení záznamu pro druhého recenzenta
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("siii",$reviewDate, $reviewer2Id, $recordId, $version);
        $stmt->execute();
        $stmt->close();
    }

    // Aktualizace stavu článku na 2 - recenzní řízení
    $query = "UPDATE PRISPEVEK SET STAV = 2 WHERE ID = ?";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $stmt->close();
    }

    // vlozeni zprav recenzentum ze maji clanek k recenzi
    $qeuryArticleName = "SELECT NAZEV FROM PRISPEVEKVER WHERE ID_PRISPEVKU = " . $recordId . " ORDER BY VERZE DESC LIMIT 1";
    $resultArticleName = $mysqli->query($qeuryArticleName);
    $rowArticleName = $resultArticleName->fetch_assoc();
    $articleName = $rowArticleName['NAZEV'];

    $od = $loggedUserID;
    $predmet = "Článek připraven k recenzi: " . $articleName;
    $text = "Článek: " . $articleName . " Vám byl zaslán k recenzi jakožto nejlepšímu v oboru, zrecenzujte ho prosím do: " . $reviewDate;

    // Zpravy pro oba recenzenty
    $queryInsertZprava = "INSERT INTO ZPRAVY (OD, KOMU, PREDMET, TEXT) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($queryInsertZprava);
    $stmt->bind_param("iiss",  $od, $reviewer1Id, $predmet, $text);
    $stmt->execute();

    $queryInsertZprava = "INSERT INTO ZPRAVY (OD, KOMU, PREDMET, TEXT) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($queryInsertZprava);
    $stmt->bind_param("iiss",  $od, $reviewer2Id, $predmet, $text);
    $stmt->execute();


    $mysqli->close();
    echo "Recenze byly úspěšně přiřazeny.";
    // Zde můžete provést další akce, jako je přesměrování nebo aktualizace stránky
}

?>