<?php
require_once "../config/config.php";
require_once "../includes/db_connect.php";


$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$response = ['status' => 'error'];

$mysqli = DbConnect::connect();

$query = "SELECT HESLO, ROLE FROM PROFIL WHERE LOGIN = ?";

if ($stmt = $mysqli->prepare($query)) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['HESLO'];

        if ($password === $storedPassword) {
            session_start();
            $_SESSION[SESSION_VAR_USER_IS_LOGGED] = 1;
            $_SESSION[SESSION_VAR_USER_NAME] = $username;
            $_SESSION[SESSION_VAR_USER_ROLE] = getRoleName($row['ROLE']); // Přiřazení role
            $response['status'] = 'success';
        }
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);

// Funkce pro převod číselné hodnoty role na textový popis - pro potřeby zobrazení v hlavičcce
function getRoleName($roleId) {
    switch ($roleId) {
        case 1:
            return 'Autor';
        case 2:
            return 'Recenzent';
        case 3:
            return 'Redaktor';
        // ... další role ...
        default:
            return 'Neznámá Role';
    }
}
?>