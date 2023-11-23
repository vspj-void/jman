<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";
require_once "classes/session_info.php";

$articlesPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articlesPerPage;

// Inicializace objektu $mysqli
$mysqli = DbConnect::connect();

// Zpracování akce zveřejnění článku
if (isset($_GET['action']) && $_GET['action'] == 'publishArticle' && isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];

    // Aktualizovat stav článku na 0 (zveřejnění článku)
    $updateStatusQuery = "UPDATE PRISPEVEK SET STAV = 0 WHERE ID = $articleId";
    $mysqli->query($updateStatusQuery);
    //Zabraňuje vypsání chyby, že článek už byl odeslán.
    if (!headers_sent()) {
        header('Location: redaktor-article.php');
        exit();
    } else {
        // Zde můžeš přidat alternativní kód nebo zprávu, pokud nemůžeš přesměrovat
        echo 'Probíhá přesměrování...';
        echo '<meta http-equiv="refresh" content="0;URL=\'redaktor-article.php\'" />';
        exit();
    }
    // Přesměrovat zpět na stránku redaktora
    header("Location: redaktor-article.php'");
    exit();
}


// Zpracování akce zamítnutí článku
if (isset($_GET['action']) && $_GET['action'] == 'rejectArticle' && isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];
    // Aktualizovat stav článku na -1 (zamítnutí článku)
    $updateStatusQuery = "UPDATE PRISPEVEK SET STAV = -1 WHERE ID = $articleId";
    $mysqli->query($updateStatusQuery);

    // vlozeni zpravy pro autora do db
    $loggedUser = SessionInfo::getLoggedOsoba();
    $qeuryArticleName = "SELECT NAZEV FROM PRISPEVEKVER WHERE ID_PRISPEVKU = " . $articleId . " ORDER BY VERZE DESC LIMIT 1";
    $resultArticleName = $mysqli->query($qeuryArticleName);
    $rowArticleName = $resultArticleName->fetch_assoc();
    $articleName = $rowArticleName['NAZEV'];
    $queryAutorIDs = "SELECT A.ID_OSOBY FROM AUTORI A JOIN OSOBA O ON A.ID_OSOBY=O.ID WHERE ID_PRISPEVKU=" . $articleId . " AND O.LOGIN IS NOT NULL";
    $resultAutorIDs = $mysqli->query($queryAutorIDs);

    $od = $loggedUser->getId();
    $predmet = "Zamítnutí článku: " . $articleName;
    $text = "Váš článek " . $articleName . " byl zamítnut.";

    if ($resultAutorIDs) {
        while ($row = $resultAutorIDs->fetch_assoc()) {
            $autorId = $row['ID_OSOBY'];
            $queryInsertZprava = "INSERT INTO ZPRAVY (OD, KOMU, PREDMET, TEXT) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($queryInsertZprava);
            $stmt->bind_param("iiss",  $od, $autorId, $predmet, $text);
            $stmt->execute();
        }
    }
    //Zabraňuje vypsání chyby, že článek už byl odeslán.
    if (!headers_sent()) {
        header('Location: redaktor-article.php');
        exit();
    } else {
        // Zde můžeš přidat alternativní kód nebo zprávu, pokud nemůžeš přesměrovat
        echo 'Probíhá přesměrování...';
        echo '<meta http-equiv="refresh" content="0;URL=\'redaktor-article.php\'" />';
        exit();
    }
    // Přesměrovat zpět na stránku redaktora
    header("Location: redaktor-article.php'");
    exit();
}

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    // var_dump($articleFile);
}

?>


<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Redaktor</h1>
        <p class="lead">Nově přidané články</p>
        <hr class="my-4">
    </div>
</div>
<br />
<div class="container mt-6 d-flex align-items-center">
    <form method="GET" action="?page=<?= $page; ?>" class="row">
        <label for="searchInput" class="col-md-4">Vyhledejte si článek:</label>
        <div class="form-group col-md-9"> <!--šířka vyhledávacího pole-->
            <input type="text" class="form-control" id="searchInput" name="search" placeholder="Zadejte název článku, nebo příjmení autora">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary">Vyhledat</button>
        </div>
    </form>
</div>

<?php
// Přidaný kód pro řazení a vyhledávání
$searchTerm = isset($_GET['search']) ? strval($_GET['search']) : null;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV'; // defaultní řazení podle Názvu
$sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

// Dotaz pro získání článků s možností řazení
$queryArticlesBase = "
SELECT
    DISTINCT PV.NAZEV,
    PV.CESTA,
    PV.ID_PRISPEVKU,
    GROUP_CONCAT(CONCAT(O.JMENO, ' ', O.PRIJMENI) SEPARATOR ', ') AS AUTORSKY_TYM,
    C.TEMA AS CASOPIS_TEMA
FROM
    PRISPEVEKVER PV
    INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
    INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
    INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
    INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
WHERE
    P.STAV = 1
GROUP BY
    P.ID,
    PV.VERZE";

// Pokud je zadán vyhledávací termín
if (!is_null($searchTerm)) {
    $searchCondition = "ZPV.NAZEV LIKE '%$searchTerm%' OR AUTORSKY_TYM LIKE '%$searchTerm%'";

    $queryArticlesBase = "
            SELECT * FROM ($queryArticlesBase) AS ZPV
            WHERE $searchCondition";
}

$queryArticles = $queryArticlesBase;
$queryArticles .= " ORDER BY $sortBy $sortOrder LIMIT $offset, $articlesPerPage";

$resultArticles = $mysqli->query($queryArticles);

?>

<script type="text/javascript">
    // potvrzovací okno na zveřejnění / zamítnutí článku
    function confirmAction(url, message) {
        if (confirm(message)) {
            window.location.href = url;
        }
    }
</script>

<div class="container mt-4">
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <!-- Odkazy na řazení -->
                <th scope="col"><a href="?page=<?= $page; ?>&sort=NAZEV&order=<?= ($sortBy === 'NAZEV' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Název</a></th>
                <th scope="col"><a href="?page=<?= $page; ?>&sort=AUTORSKY_TYM&order=<?= ($sortBy === 'AUTORSKY_TYM' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Autor</a></th>
                <th scope="col"><a href="?page=<?= $page; ?>&sort=TEMA&order=<?= ($sortBy === 'TEMA' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Téma časopisu</a></th>
                <th scope="col">Otevřít článek</th>
                <th scope="col">Akce</th> <!-- Nový sloupec pro tlačítko -->
            </tr>
        </thead>
        <tbody>
            <?php $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV'; // defaultní řazení podle Názvu
            while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                <tr>
                    <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                    <td><?= isset($rowArticle["AUTORSKY_TYM"]) && isset($rowArticle["AUTORSKY_TYM"]) ? $rowArticle["AUTORSKY_TYM"] : "Autor není k dispozici"; ?></td>
                    <td><?= !empty($rowArticle["CASOPIS_TEMA"]) ? $rowArticle["CASOPIS_TEMA"] : "Téma není k dispozici"; ?></td>
                    <td><a href="<?= isset($rowArticle["CESTA"]) ? (UPLOAD_ARTICLES_URL . "/") . $rowArticle["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                    <td>
                        <?php
                        // Kontrola, zda článek již není zveřejněn
                        if (!isset($rowArticle["STAV"]) || $rowArticle["STAV"] != 0) {
                            echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=publishArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zveřejnit tento článek?\')" class="btn btn-success">Zveřejnit článek</a>';
                            echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=rejectArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zamítnout tento článek?\')" class="btn btn-danger">Zamítnout článek</a>';
                        } else {
                            echo '<span class="text-success">Článek je již zveřejněn</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!--Žádný záznam neodpovídá požadavkům. -->
<div class="container mt-4">
    <?php if ($resultArticles->num_rows > 0) : ?>
        <table class="table table-bordered table-striped">
            <!-- Tabulka obsahuje záznamy -->
        </table>
    <?php else : ?>
        <h5>Žádný záznam neodpovídá požadavkům.</h5>
    <?php endif; ?>
</div>

<!--tlačítko na zobrazení všeho-->
<div class="container mt-4">
    <form method="GET" action="?page=<?= $page; ?>" class="row">
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Zobrazit všechny články</button>
        </div>
    </form>
</div>

<div class="container">
    <ul class="pagination justify-content-center">
        <?php
        $totalArticlesQuery = "SELECT COUNT(*) total FROM ($queryArticlesBase) articles";
        $totalArticlesResult = $mysqli->query($totalArticlesQuery);
        $totalArticles = $totalArticlesResult->fetch_assoc()['total'];

        $totalPages = ceil($totalArticles / $articlesPerPage);

        for ($i = 1; $i <= $totalPages; $i++) :
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</div>