<?php
// Přidává nového autora bez loginu

require_once "../includes/db_connect.php";

// Handle AJAX request to add Osoba
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["mail"]) || !isset($_POST["jmeno"]) || !isset($_POST["prijmeni"])) {
    // Invalid request
    header('HTTP/1.1 400 Bad Request');
    echo "Invalid request.";
}

// Validate and sanitize input data
$mail = filter_var($_POST["mail"], FILTER_SANITIZE_STRING);
$jmeno = filter_var($_POST["jmeno"], FILTER_SANITIZE_STRING);
$prijmeni = filter_var($_POST["prijmeni"], FILTER_SANITIZE_STRING);

// Create a prepared statement
$stmt = $mysqli->prepare("INSERT INTO OSOBA (MAIL, JMENO, PRIJMENI) VALUES (?, ?, ?)");

// Bind parameters
$stmt->bind_param("sss", $mail, $jmeno, $prijmeni);

// Execute the statement
$result = $stmt->execute();

$response = ["success" => $result, "message" => $result ? "OK" : "Error: {$mysqli->error}", "id" => $result ? $mysqli->insert_id : null];

// Close the database connection
$mysqli->close();

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);