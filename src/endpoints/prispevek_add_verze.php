<?php
// Přidání nové verze příspěvku

require_once "../includes/db_connect.php";

function stripAccents($str)
{
    return strtr(utf8_decode($str), utf8_decode("àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ"), "aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY");
}

function getUniqueFileName($originalFileName)
{
    $pathInfo = pathinfo($originalFileName);
    $originalFileName = $pathInfo["filename"];

    // Replace spaces with underscores
    $newFileName = str_replace(" ", "_", $originalFileName);

    // Remove non-ASCII characters
    $newFileName = stripAccents($newFileName);

    // Add a timestamp to make the filename unique
    $timestamp = time();
    $newFileName = $newFileName . "_" . $timestamp . "." . $pathInfo["extension"];

    return $newFileName;
}

function saveFile($file, $targetFileName)
{
    $sourceFileName = $file["tmp_name"];

    if (file_exists($targetFileName)) {
        die("The file '$targetFileName' already exists.");
    }

    if (!move_uploaded_file($sourceFileName, $targetFileName)) {
        die("The uploaded file '$sourceFileName' could not be moved to '$targetFileName'.");
    }

    // Nastav práva na R/W/X, ať můžeme soubor v případě potřeby sami spravovat
    chmod($targetFileName, 0777);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nazev = $_POST['prispevekName'];
    $idPrispevku = $_POST['prispevekId'];
    $prispevekFile = $_FILES["prispevekFile"];

    $prispevekFileName = getUniqueFileName($prispevekFile["name"]);
    // Vytvoř novou složku pro upload, pokud neexistuje
    if (!file_exists(UPLOAD_ARTICLES_DIRECTORY)) {
        mkdir(UPLOAD_ARTICLES_DIRECTORY, 0777, true);
    
        // Nastav práva na R/W/X, ať můžeme složku v případě potřeby sami spravovat
        chmod(UPLOAD_ARTICLES_DIRECTORY, 0777);
    }
    
    // Ulož soubor příspěvku
    saveFile($prispevekFile, UPLOAD_ARTICLES_DIRECTORY . DIRECTORY_SEPARATOR . $prispevekFileName);


    $mysqli = DbConnect::connect();
    // Nejprve zjistíme nejvyšší verzi pro daný ID_PRISPEVKU
    $verzeQuery = "SELECT MAX(VERZE) AS max_verze FROM PRISPEVEKVER WHERE ID_PRISPEVKU = ?";
    $verzeStmt = $mysqli->prepare($verzeQuery);
    $verzeStmt->bind_param("i", $idPrispevku);
    $verzeStmt->execute();
    $verzeResult = $verzeStmt->get_result();
    $row = $verzeResult->fetch_assoc();
    $novaVerze = $row['max_verze'] + 1;

    // SQL dotaz pro vložení nového záznamu
    $insertQuery = "INSERT INTO PRISPEVEKVER (VERZE, NAZEV, CESTA, ID_PRISPEVKU) VALUES (?, ?, ?, ?)";
    $insertStmt = $mysqli->prepare($insertQuery);
    $insertStmt->bind_param("issi", $novaVerze, $nazev, $prispevekFileName, $idPrispevku);
    $insertStmt->execute();

    if ($insertStmt->affected_rows > 0) {
        echo "Nový záznam byl úspěšně vytvořen";
    } else {
        echo "Nepodařilo se vytvořit nový záznam";
    }

    $verzeStmt->close();
    $insertStmt->close();
    $mysqli->close();
}
?>