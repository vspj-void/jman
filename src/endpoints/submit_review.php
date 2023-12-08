<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once "../includes/db_connect.php";
    $mysqli = DbConnect::connect();


    // Přiřazení dat z formuláře do proměnných
    $ratingRelevance = $_POST['ratingRelevance'];
    $ratingInterest = $_POST['ratingInterest'];
    $ratingOriginality = $_POST['ratingOriginality'];
    $ratingLanguage = $_POST['ratingLanguage'];
    $additionalComments = $_POST['additionalComments'];
    $idPrispevku = $_POST['idPrispevku']; // Předpokládám, že máte ID příspěvku
    $idRecenze = $_POST['idRecenze'];

    // Uložení komentáře do textového souboru
    $filename = "/review_" . $idPrispevku . "_" . time() . ".txt";
    $filepath = "../upload/reviews/" . $filename;
    file_put_contents($filepath, $additionalComments);

    // Aktualizace databáze
    $sql = "UPDATE PRISPEVEK SET STAV = CASE WHEN STAV = 2 THEN 3 WHEN STAV = 3 THEN 4 ELSE STAV END WHERE ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $idPrispevku);
    $stmt->execute();

    // Aktualizace tabulky recenze
    $sql = "UPDATE RECENZE SET CESTA = ?, ODBORNOST = ?, JAZYKOVA_UROVEN = ?, AKTUALNOST = ?, ORIGINALITA = ?, DATUM_RECENZE = CURRENT_DATE() WHERE ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("siiiii", $filename, $ratingRelevance, $ratingInterest, $ratingOriginality, $ratingLanguage, $idRecenze);
    $stmt->execute();


    // Další kód, například přesměrování nebo zobrazení zprávy o úspěchu
}
?>