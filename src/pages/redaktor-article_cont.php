
<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";

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

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    // var_dump($articleFile);
}

// Zpracování akce zamítnutí článku
if (isset($_GET['action']) && $_GET['action'] == 'rejectArticle' && isset($_GET['articleId'])) {
    $articleId = $_GET['articleId'];

    // Aktualizovat stav článku na -1 (zamítnutí článku)
    $updateStatusQuery = "UPDATE PRISPEVEK SET STAV = -1 WHERE ID = $articleId";
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

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Redaktor</h1>
        <p class="lead">nově přidané články</p>
        <hr class="my-4">
    </div>
</div>
<br/>
<div class="container mt-6 d-flex align-items-center">
    <form method="GET" action="?page=<?= $page; ?>" class="row">
        <label for="searchInput" class="col-md-4">Vyhledejte si článek:</label>
        <div class="form-group col-md-9">  <!--šířka vyhledávacího pole-->
            <input type="text" class="form-control" id="searchInput" name="search" placeholder="Zadejte název článku, nebo příjmení autora">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary">Vyhledat</button>
        </div>
    </form>
</div>

<?php
// Přidaný kód pro řazení a vyhledávání
$searchTerm = isset($_GET['search']) ? $_GET['search'] : ''; 
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV'; // defaultní řazení podle Názvu
$sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

// Dotaz pro získání článků s možností řazení
$queryArticles = "SELECT PV.*, O.JMENO as AUTOR_JMENO, O.PRIJMENI as AUTOR_PRIJMENI, C.TEMA as CASOPIS_TEMA 
                  FROM PRISPEVEKVER PV
                  INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                  INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                  INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
                  INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
                  WHERE P.STAV = 1";

// Pokud je zadán vyhledávací termín
if (!empty($searchTerm)) {
    $queryArticles .= " AND (PV.NAZEV LIKE '%$searchTerm%' OR O.PRIJMENI LIKE '%$searchTerm%')";
}

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
                <th scope="col"><a href="?page=<?= $page; ?>&sort=AUTOR_PRIJMENI&order=<?= ($sortBy === 'AUTOR_PRIJMENI' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Autor</a></th>
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
                    <td><?= isset($rowArticle["AUTOR_JMENO"]) && isset($rowArticle["AUTOR_PRIJMENI"]) ? $rowArticle["AUTOR_JMENO"] . " " . $rowArticle["AUTOR_PRIJMENI"] : "Autor není k dispozici"; ?></td>
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
        $totalArticlesQuery = "SELECT COUNT(*) as total FROM PRISPEVEKVER PV
                               INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                               INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                               WHERE P.STAV = 1";
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
