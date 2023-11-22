<?php
require_once "../includes/db_connect.php";
$mysqli = DbConnect::connect();

if (isset($_POST['id']) && isset($_POST['precteno'])) {
    $id = intval($_POST['id']);
    $precteno = intval($_POST['precteno']) === 1 ? 1 : 0;

    // Zde vypisujete proměnné pro kontrolu
    var_dump($id);
    var_dump($precteno);

    $query = "UPDATE ZPRAVY SET PRECTENO = ? WHERE ID = ?";
    $stmt = $mysqli->prepare($query);

    if ($stmt) {
        $stmt->bind_param('ii', $precteno, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Stav PRECTENO byl úspěšně aktualizován.";
        } else {
            echo "Nepodařilo se aktualizovat stav PRECTENO.";
        }

        $stmt->close();
    } else {
        echo "Chyba při přípravě dotazu.";
    }
} else {
    echo "Nedostatečné parametry.";
}

$mysqli->close();
?>
