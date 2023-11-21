<?php

require_once "../config/config.php";

// Fejková databáze - uživatelské jméno: redaktor, heslo: redaktor
$expectedUsername = 'testuser';
$expectedPassword = 'testuser';

// Získání dat z AJAX požadavku
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Inicializace odpovědi
$response = ['status' => 'error'];

// Kontrola uživatelského jména a hesla
if ($username === $expectedUsername && $password === $expectedPassword) {
    // Údaje jsou správné, uživatel je "přihlášen"
    session_start();
    $_SESSION[SESSION_VAR_USER_IS_LOGGED] = 1;
    $_SESSION[SESSION_VAR_USER_NAME] = $username; // Přidání jména uživatele do session
    $response['status'] = 'success';
}

// Odeslání odpovědi zpět do JavaScriptu
header('Content-Type: application/json');
echo json_encode($response);
?>