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
        if (!headers_sent()) {
            header('Location: redaktor-article-complete-list.php');
            exit();
        } else {
            echo 'Probíhá přesměrování...';
            echo '<meta http-equiv="refresh" content="0;URL=\'redaktor-article-complete-list.php\'" />';
            exit();
        }
        header("Location: redaktor-article-complete-list.php");
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
            header('Location: redaktor-article-complete-list.php');
            exit();
        } else {
            // Zde můžeš přidat alternativní kód nebo zprávu, pokud nemůžeš přesměrovat
            echo 'Probíhá přesměrování...';
            echo '<meta http-equiv="refresh" content="0;URL=\'redaktor-article-complete-list.php\'" />';
            exit();
        }
        // Přesměrovat zpět na stránku redaktora
        header("Location: redaktor-article-complete-list.php");
        exit();
    }

    ?>

    <div class="container mt-4">
        <div class="jumbotron">
            <h1 class="display-4">Redaktor</h1>
            <p class="lead">Přehled všech článků</p>
            <hr class="my-4">
        </div>
    </div>
    <br/>
    <div class="container mt-6 d-flex align-items-center">
        <form method="GET" action="?page=<?= $page; ?>" class="row">
            <label for="searchInput" class="col-md-4">Vyhledejte si článek:</label>
            <div class="form-group col-md-9">
                <input type="text" class="form-control" id="searchInput" name="search" placeholder="Zadejte název článku, nebo příjmení autora">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary">Vyhledat</button>
            </div>
        </form>
    </div>

    <?php
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : ''; 
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV';
    $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

    $queryArticles = "SELECT PV.*, O.JMENO as AUTOR_JMENO, O.PRIJMENI as AUTOR_PRIJMENI, C.TEMA as CASOPIS_TEMA, P.STAV 
                    FROM PRISPEVEKVER PV
                    INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
                    INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
                    INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
                    INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID";

    if (!empty($searchTerm)) {
        $queryArticles .= " AND (PV.NAZEV LIKE '%$searchTerm%' OR O.PRIJMENI LIKE '%$searchTerm%')";
    }

    // filtr na Stav
    if (isset($_GET['statusFilter']) && $_GET['statusFilter'] != 'all') {
        $statusFilter = $_GET['statusFilter'];
        $queryArticles .= " AND P.STAV = $statusFilter";
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
                    <th scope="col">Název</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Téma časopisu</th>
                    <th scope="col">Otevřít článek</th>
                    <th scope="col">
                        <div class="d-flex align-items-end">
                            Stav
                            <div class="dropdown ms-3">
                                <button class="btn btn-secondary " type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-filter"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item" href="?statusFilter=all">Všechny</a></li>
                                    <li><a class="dropdown-item" href="?statusFilter=1">Nově podaný</a></li>
                                    <li><a class="dropdown-item" href="?statusFilter=0">Zveřejněný</a></li>
                                    <li><a class="dropdown-item" href="?statusFilter=-1">Zamítnutý</a></li>
                                </ul>
                            </div>
                        </div>
                    </th>
                    <th scope="col">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                    <tr>
                        <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                        <td><?= isset($rowArticle["AUTOR_JMENO"]) && isset($rowArticle["AUTOR_PRIJMENI"]) ? $rowArticle["AUTOR_JMENO"] . " " . $rowArticle["AUTOR_PRIJMENI"] : "Autor není k dispozici"; ?></td>
                        <td><?= !empty($rowArticle["CASOPIS_TEMA"]) ? $rowArticle["CASOPIS_TEMA"] : "Téma není k dispozici"; ?></td>
                        <td><a href="<?= isset($rowArticle["CESTA"]) ? (UPLOAD_ARTICLES_URL . "/") . $rowArticle["CESTA"] : "#"; ?>" class="btn btn-primary" target="_blank">Otevřít článek</a></td>
                        <td>
                            <?php
                            switch ($rowArticle["STAV"]) {
                                case 1:
                                    echo "Nově podaný";
                                    break;
                                case 0:
                                    echo "Zveřejněný";
                                    break;
                                case -1:
                                    echo "Zamítnutý";
                                    break;
                                default:
                                    echo "V procesu";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            switch ($rowArticle["STAV"]) {
                                case 1:
                                    echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=publishArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zveřejnit tento článek?\')" class="btn btn-success">Zveřejnit článek</a>';
                                    echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=rejectArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zamítnout tento článek?\')" class="btn btn-danger">Zamítnout článek</a>';
                                    break;
                                default:
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="container mt-4">
        <?php if ($resultArticles->num_rows > 0) : ?>
            <table class="table table-bordered table-striped">
                <!-- Tabulka obsahuje záznamy -->
            </table>
        <?php else : ?>
            <h5>Žádný záznam neodpovídá požadavkům.</h5>
        <?php endif; ?>
    </div>

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