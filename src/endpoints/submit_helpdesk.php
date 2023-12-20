<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once "../includes/db_connect.php"; // Připojení k databázi
    $conn = DbConnect::connect();

    // Kontrola spojení
    if ($conn->connect_error) {
        die("Spojení se nezdařilo: " . $conn->connect_error);
    } else {
        echo "Spojení bylo úspěšné.";
    }
    // Přiřazení dat z formuláře do proměnných
    $name = $_POST['name'];
    $email = $_POST['email'];
    $problem = $_POST['problem'];
    $description = $_POST['description'];

    // Ošetření vstupů a případné další validace

    // Příprava SQL dotazu pro vložení dat
    $sql = "INSERT INTO HELPDESKKRIDLO (name, email, problem, description) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $problem, $description);

    // Spuštění SQL dotazu
    if ($stmt->execute()) {
        // Zde můžete přidat logiku po úspěšném vložení, například přesměrování nebo zobrazení zprávy
        echo "Žádost byla úspěšně odeslána.";
    } else {
        // Zde můžete zpracovat chyby
        echo "Chyba: " . $stmt->error;
        print_r($stmt->errorInfo());
    }

    // Uzavření příkazu a připojení
    $stmt->close();
    $conn->close();
}
?>