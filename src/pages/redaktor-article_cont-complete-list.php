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
    <br />
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
    $searchTerm = isset($_GET['search']) ? strval($_GET['search']) : null;
    $statusFilter = isset($_GET['statusFilter']) && $_GET['statusFilter'] != 'all' ? strval($_GET['statusFilter']) : null; // filtr na Stav
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV';
    $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

    $queryArticlesBase = "
    WITH NEJNOVEJSI_VERZE AS (
        SELECT
            PRI.ID_PRISPEVKU,
            MAX(PRI.VERZE) AS MAX_VERZE
        FROM
            PRISPEVEKVER PRI
        GROUP BY
            ID_PRISPEVKU
    )
    SELECT
        PV.*,
        GROUP_CONCAT(CONCAT(O.JMENO, ' ', O.PRIJMENI) SEPARATOR ', ') AS AUTORSKY_TYM,
        C.TEMA AS CASOPIS_TEMA,
        P.STAV
    FROM
        PRISPEVEKVER PV
        INNER JOIN NEJNOVEJSI_VERZE ON (PV.ID_PRISPEVKU = NEJNOVEJSI_VERZE.ID_PRISPEVKU AND PV.VERZE = NEJNOVEJSI_VERZE.MAX_VERZE)
        INNER JOIN PRISPEVEK P ON PV.ID_PRISPEVKU = P.ID
        INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
        INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
        INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
    GROUP BY
        P.ID,
        PV.VERZE";


    // Pokud je zadán vyhledávací termín
    if (!is_null($searchTerm) || !is_null($statusFilter)) {
        $searchCondition = "";

        if (!is_null($searchTerm)) {
            $searchCondition .= "ZPV.NAZEV LIKE '%$searchTerm%' OR AUTORSKY_TYM LIKE '%$searchTerm%'";
        }

        if (!is_null($statusFilter)) {
            if (!is_null($searchTerm)) {
                $searchCondition .= " AND ";
            }

            $searchCondition .= " ZPV.STAV = $statusFilter";
        }

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
                    <th scope="col">Verze</th>
                    <th scope="col">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                    <tr>
                        <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                        <td><?= isset($rowArticle["AUTORSKY_TYM"]) && isset($rowArticle["AUTORSKY_TYM"]) ? $rowArticle["AUTORSKY_TYM"] : "Autor není k dispozici"; ?></td>
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
                            <?= $rowArticle["VERZE"]; ?>
                            <!-- Přidání tlačítka pro zobrazení všech verzí -->
                            <a href="redaktor-one-article-all-versions.php?articleId=<?= $rowArticle['ID_PRISPEVKU']; ?>" class="btn btn-info btn-sm">Zobrazit verze</a>
                        </td>
                        <td>
                            <?php
                            switch ($rowArticle["STAV"]) {
                                case 1:
                                    echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=publishArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zveřejnit tento článek?\')" class="btn btn-success">Zveřejnit článek</a>';
                                    echo '<a href="#" onclick="confirmAction(\'?page=' . $page . '&action=rejectArticle&articleId=' . $rowArticle["ID_PRISPEVKU"] . '\', \'Opravdu chcete zamítnout tento článek?\')" class="btn btn-danger">Zamítnout článek</a>';
                                    echo '<button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reviewerModal" data-id="' . $rowArticle['ID_PRISPEVKU'] . '" data-version="' . $rowArticle['VERZE'] . '">Přiřadit recenzenty</button>';
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

    <div class="modal fade" id="reviewerModal" tabindex="-1" aria-labelledby="reviewerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vybrat recenzenty</h5>
            </div>
            <div class="modal-body">
                    <form id="reviewerForm">
                        <input type="hidden" id="recordId" name="recordId">
                        <input type="hidden" id="version" name="version">
                        <input type="hidden" id="userID" name="userID" value="<?php echo SessionInfo::getLoggedOsoba()->getId(); ?>">
                        <select class="form-select" id="reviewer1" name="reviewer1" required>
                            <?php
                            // Dotaz pro získání recenzentů (role 2), kterým chcete poslat stuff na recenzi
                            $queryAuthorsToSendMessage = "SELECT OSOBA.ID, OSOBA.JMENO, OSOBA.PRIJMENI
                                                           FROM PROFIL
                                                           JOIN OSOBA ON OSOBA.LOGIN = PROFIL.LOGIN
                                                           WHERE PROFIL.ROLE = 2";
                            $stmtAuthorsToSendMessage = $mysqli->prepare($queryAuthorsToSendMessage);
                            $stmtAuthorsToSendMessage->execute();
                            $resultAuthorsToSendMessage = $stmtAuthorsToSendMessage->get_result();
                        
                            while ($row = $resultAuthorsToSendMessage->fetch_assoc()) {
                                echo '<option value="' . $row['ID'] . '">' . $row['JMENO'] . ' ' . $row['PRIJMENI'] . '</option>';
                            }
                            ?>
                        </select>

                        <select class="form-select" id="reviewer2" name="reviewer2" required>
                            <?php
                            // Dotaz pro získání recenzentů (role 2), kterým chcete poslat stuff na recenzi
                            $queryAuthorsToSendMessage = "SELECT OSOBA.ID, OSOBA.JMENO, OSOBA.PRIJMENI
                                                           FROM PROFIL
                                                           JOIN OSOBA ON OSOBA.LOGIN = PROFIL.LOGIN
                                                           WHERE PROFIL.ROLE = 2";
                            $stmtAuthorsToSendMessage = $mysqli->prepare($queryAuthorsToSendMessage);
                            $stmtAuthorsToSendMessage->execute();
                            $resultAuthorsToSendMessage = $stmtAuthorsToSendMessage->get_result();
                        
                            while ($row = $resultAuthorsToSendMessage->fetch_assoc()) {
                                echo '<option value="' . $row['ID'] . '">' . $row['JMENO'] . ' ' . $row['PRIJMENI'] . '</option>';
                            }
                            ?>
                        </select>
                        <label for="reviewDate">Termín recenze:</label>
                        <input type="date" class="form-control" id="reviewDate" name="reviewDate" required>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                    <button type="button" class="btn btn-primary" onclick="submitReviewers()">Odeslat</button>
                </div>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function() {
        // Tento kód se spustí, když je modální okno otevřeno
        $('#reviewerModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Tlačítko, které spustilo modální okno
            var recordId = button.data('id'); // Získání dat z data-id
            var version = button.data('version');
            $('#recordId').val(recordId);
            $('#version').val(version);
        });

        // Funkce pro odeslání dat formuláře
        window.submitReviewers = function() {
            var formData = $('#reviewerForm').serialize(); // Serializuje všechna data formuláře, včetně recordId
            
            $.ajax({
                type: 'POST',
                url: 'endpoints/send_to_reviewers.php',
                data: formData,
                success: function(response) {
                    $('#reviewerModal').modal('hide'); // Zavření modálního okna
                },
                error: function() {
                    // Chyba při odesílání
                    alert('Došlo k chybě při odesílání. Zkuste to prosím znovu.');
                }
            });
        };
    });
</script>
   



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
