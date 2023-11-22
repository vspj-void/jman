<?php
// Přidává nový článek

require_once "../includes/db_connect.php";

// https://stackoverflow.com/a/11743977
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

if (
    $_SERVER["REQUEST_METHOD"] != "POST"
    || !isset($_POST["prispevekName"])
    || !isset($_POST["prispevekVersion"])
    || !isset($_POST["prispevekAuthorIds"])
    || !isset($_FILES["prispevekFile"])
) {
    // Invalid request
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid request.";
}

$prispevekName = $_POST["prispevekName"];
$prispevekFile = $_FILES["prispevekFile"];
$prispevekVersion = $_POST["prispevekVersion"];
$prispevekAuthorIds = $_POST["prispevekAuthorIds"];

// V případě, že přijde pouze jediné ID, je potřeba jej převést na array, aby přes něj šlo iterovat
if (is_string($prispevekAuthorIds)) {
    $prispevekAuthorIds = explode(",", $prispevekAuthorIds);
} else if (!is_array($prispevekAuthorIds)) {
    $prispevekAuthorIds = [$prispevekAuthorIds];
}

$prispevekCasopisId = $_POST["prispevekCasopisId"];


$prispevekFileName = getUniqueFileName($prispevekFile["name"]);

// Vytvoř novou složku pro upload, pokud neexistuje
if (!file_exists(UPLOAD_ARTICLES_DIRECTORY)) {
    mkdir(UPLOAD_ARTICLES_DIRECTORY, 0777, true);

    // Nastav práva na R/W/X, ať můžeme složku v případě potřeby sami spravovat
    chmod(UPLOAD_ARTICLES_DIRECTORY, 0777);
}

// Ulož soubor příspěvku
saveFile($prispevekFile, UPLOAD_ARTICLES_DIRECTORY . DIRECTORY_SEPARATOR . $prispevekFileName);

// Zvaliduj vstupní data
$prispevekName = filter_var($prispevekName, FILTER_SANITIZE_STRING);

$mysqli = DbConnect::connect();

try {
    $mysqli->begin_transaction();

    $queryPrispevek = "INSERT INTO PRISPEVEK (ID_CASOPISU) VALUES (?)";

    $stmtPrispevek = $mysqli->prepare($queryPrispevek);
    $stmtPrispevek->bind_param("i", $prispevekCasopisId);
    $resultPrispevek = $stmtPrispevek->execute();

    if (!$resultPrispevek) {
        // Vložení příspěvku selhalo
        $mysqli->rollback();

        $response = ["success" => false, "message" => "Could not insert into prispevek due to: {$mysqli->error}"];
        header("Content-Type: application/json");
        echo json_encode($response);
        return;
    }

    $prispevekId = $mysqli->insert_id;


    $queryPrispevekVer = "INSERT INTO PRISPEVEKVER (VERZE, NAZEV, CESTA, ID_PRISPEVKU) VALUES (?, ?, ?, ?)";

    $stmtPrispevekVer = $mysqli->prepare($queryPrispevekVer);
    $stmtPrispevekVer->bind_param("issi", $prispevekVersion, $prispevekName, $prispevekFileName, $prispevekId);
    $resultPrispevekVer = $stmtPrispevekVer->execute();

    if (!$resultPrispevekVer) {
        // Vložení příspěvku selhalo
        $mysqli->rollback();

        $response = ["success" => false, "message" => "Could not insert into prispevek due to: {$mysqli->error}"];
        header("Content-Type: application/json");
        echo json_encode($response);
        return;
    }

    foreach ($prispevekAuthorIds as $authorId) {
        $resultAuthor = $mysqli->query("INSERT INTO AUTORI (ID_OSOBY, ID_PRISPEVKU, VERZE) VALUES ($authorId, $prispevekId, $prispevekVersion)");

        if (!$resultAuthor) {
            // Vložení autorství selhalo
            $mysqli->rollback();

            $response = ["success" => false, "message" => "Could not insert into autori due to: {$mysqli->error}"];
            header("Content-Type: application/json");
            echo json_encode($response);
            return;
        }
    }

    if (!$mysqli->commit()) {
        $response = ["success" => false, "message" => "Commit failed: {$mysqli->error}"];
        header("Content-Type: application/json");
        echo json_encode($response);
        return;
    }

    $response = ["success" => true, "message" => "OK", "prispevekId" => $prispevekId];
    header("Content-Type: application/json");
    echo json_encode($response);
} catch (Exception $e) {
    $mysqli->rollback();
}
