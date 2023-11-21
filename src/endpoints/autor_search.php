<?php
// Vyhledává existující autory (s loginem i bez loginu) podle jména a příjmení

require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] != "POST" || (!isset($_POST["queryJmeno"]) && !isset($_POST["queryPrijmeni"]))) {
    // Invalid request
    header('HTTP/1.1 400 Bad Request');
    echo "Invalid request.";
}

$searchTermJmeno = $_POST["queryJmeno"] ?? "";
$searchTermPrijmeni = $_POST["queryPrijmeni"] ?? "";

$mysqli = DbConnect::connect();

// Vyhledej autora (login je null nebo role jeho profilu je 1, tj. role autor) podle jména a příjmení
$query = "SELECT OSOBA.ID, OSOBA.MAIL, OSOBA.JMENO, OSOBA.PRIJMENI
          FROM OSOBA
          LEFT JOIN PROFIL ON OSOBA.LOGIN = PROFIL.LOGIN
          WHERE (OSOBA.LOGIN IS NULL OR PROFIL.ROLE = 1) AND OSOBA.JMENO LIKE ? AND OSOBA.PRIJMENI LIKE ?";
$stmt = $mysqli->prepare($query);

$searchTermJmeno = "%{$searchTermJmeno}%";
$searchTermPrijmeni = "%{$searchTermPrijmeni}%";
$stmt->bind_param("ss", $searchTermJmeno, $searchTermPrijmeni);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the results into an array
$osobaResults = [];
while ($row = $result->fetch_assoc()) {
    $osobaResults[] = $row;
}

// Close the database connection
$stmt->close();
$mysqli->close();

// Return the filtered Osoba data as JSON
header('Content-Type: application/json');
echo json_encode($osobaResults);
