<?php

require_once "classes/osoba.php";
require_once "includes/db_connect.php";

require_once "classes/session_info.php";

$mysqli = DbConnect::connect();

$query = "SELECT C.*, 0 AS `POCET_PRISPEVKU`, COUNT(P.ID) AS `POCET_PRISPEVKU_ZAJEM`
          FROM CASOPIS AS C
          INNER JOIN PRISPEVEK AS P
          ON P.ID_CASOPISU = C.ID
          WHERE P.STAV = 1
          GROUP BY P.ID_CASOPISU";
$result = $mysqli->query($query);

if (!$result) {
    die("Failed to query casopis.");
}

$casopisResults = $result->fetch_all(MYSQLI_ASSOC);

?>

<?php

$articlesPerPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articlesPerPage;

if (isset($_POST["articleSubmit"])) {
    $articleName = $_POST["articleName"];
    $articleFile = $_FILES["articleFile"];
    // var_dump($articleFile);
}

?>

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
    </symbol>
    <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
    </symbol>
    <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
    </symbol>
</svg>

<?php if (isset($_GET["article-success-title"])) : ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Success:">
            <use xlink:href="#check-circle-fill" />
        </svg>
        <div>
            Článek '<?= $_GET["article-success-title"]; ?>' byl úspěšně vložen.
        </div>
    </div>
<?php endif ?>

<?php if (isset($_GET["version-success"])) : ?>
    <div class="alert alert-success d-flex align-items-center" role="alert">
        <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Success:">
            <use xlink:href="#check-circle-fill" />
        </svg>
        <div>
            Nová verze článku byla úspěšně vložena.
        </div>
    </div>
<?php endif ?>

<?php
// Přidaný kód pro řazení a vyhledávání
$searchTerm = isset($_GET['search']) ? strval($_GET['search']) : null;
$statusFilter = isset($_GET['statusFilter']) && $_GET['statusFilter'] != 'all' ? strval($_GET['statusFilter']) : null; // filtr na Stav
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV'; // defaultní řazení podle Názvu
$sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

// Dotaz pro získání článků s možností řazení
$queryArticlesBase = "
WITH NEJNOVEJSI_VERZE AS (
    SELECT
        PRI.ID_PRISPEVKU,
        MAX(PRI.VERZE) AS MAX_VERZE
    FROM
        PRISPEVEKVER PRI
    GROUP BY
        ID_PRISPEVKU
),
AUTOROVY_PRISPEVKY AS (
    SELECT
        P.*
    FROM
        AUTORI A
        INNER JOIN PRISPEVEK P ON A.ID_PRISPEVKU = P.ID
    WHERE
        A.ID_OSOBY = " . SessionInfo::getLoggedOsoba()->getId() . " 
)
SELECT
    PV.*,
    GROUP_CONCAT(CONCAT(O.JMENO, ' ', O.PRIJMENI) SEPARATOR ', ') AS AUTORSKY_TYM,
    C.TEMA AS CASOPIS_TEMA,
    P.STAV
FROM
    PRISPEVEKVER PV
    INNER JOIN NEJNOVEJSI_VERZE ON (PV.ID_PRISPEVKU = NEJNOVEJSI_VERZE.ID_PRISPEVKU AND PV.VERZE = NEJNOVEJSI_VERZE.MAX_VERZE)
    INNER JOIN AUTOROVY_PRISPEVKY P ON PV.ID_PRISPEVKU = P.ID
    INNER JOIN AUTORI A ON P.ID = A.ID_PRISPEVKU
    INNER JOIN OSOBA O ON A.ID_OSOBY = O.ID
    INNER JOIN CASOPIS C ON P.ID_CASOPISU = C.ID
GROUP BY
    P.ID,
    PV.VERZE
";

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

    $queryArticlesBase = "SELECT * FROM ($queryArticlesBase) AS ZPV
                          WHERE $searchCondition";
}

$queryArticles = $queryArticlesBase;

$mysqli = DbConnect::connect();
$queryArticles .= " ORDER BY $sortBy $sortOrder LIMIT $offset, $articlesPerPage";

$resultArticles = $mysqli->query($queryArticles);

?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Autor</h1>
        <p class="lead">Moje články</p>
        <hr class="my-4">
    </div>
</div>
<br />
<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrispevekModal">Nový článek</button>
        </div>
        <div class="col-md-6">
            <form method="GET" action="?page=<?= $page; ?>" class="row input-group mb-3">
                <div class="col-6"> <!--šířka vyhledávacího pole-->
                    <input type="text" class="form-control" id="searchInput" name="search" placeholder="Zadejte název článku, nebo příjmení autora">
                </div>
                <div class="col-6">
                    <button type="submit" class="btn btn-primary btn-block">Vyhledat</button>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <!-- Odkazy na řazení -->
                <th scope="col"><a href="?page=<?= $page; ?>&sort=NAZEV&order=<?= ($sortBy === 'NAZEV' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Název</a></th>
                <th scope="col"><a href="?page=<?= $page; ?>&sort=AUTORSKY_TYM&order=<?= ($sortBy === 'AUTORSKY_TYM' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Autor</a></th>
                <th scope="col"><a href="?page=<?= $page; ?>&sort=TEMA&order=<?= ($sortBy === 'TEMA' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Téma časopisu</a></th>
                <th scope="col">Verze</th>
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
            </tr>
        </thead>
        <tbody>
            <?php $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'NAZEV'; // defaultní řazení podle Názvu
            while ($rowArticle = $resultArticles->fetch_assoc()) : ?>
                <tr>
                    <td style="display:none;"><?= $rowArticle["ID_PRISPEVKU"]; ?></td> <!-- Skryté pole pro ID -->
                    <td><?= !empty($rowArticle["NAZEV"]) ? $rowArticle["NAZEV"] : "Název není k dispozici"; ?></td>
                    <td><?= isset($rowArticle["AUTORSKY_TYM"]) ? $rowArticle["AUTORSKY_TYM"] : "Autor není k dispozici"; ?></td>
                    <td><?= !empty($rowArticle["CASOPIS_TEMA"]) ? $rowArticle["CASOPIS_TEMA"] : "Téma není k dispozici"; ?></td>
                    <td><?= !empty($rowArticle["VERZE"]) ? $rowArticle["VERZE"] : "Verze není k dispozici"; ?>
                        <button type="button" class="btn btn-info btn-sm open-modal" data-bs-toggle="modal" data-bs-target="#versionModal">Editovat</button>
                    </td>
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


<!-- Modální okno pro přidání nového příspěvku -->
<div class="modal fade" id="addPrispevekModal" tabindex="-1" aria-labelledby="addPrispevekModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPrispevekModalLabel">Nový článek</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPrispevekForm" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="prispevekCasopisId">Tématické číslo (zájem/přijato/kapacita)</label>
                            <select class="form-select" name="prispevekCasopisId" id="prispevekCasopisId" required>
                                <option value="" disabled selected>Zvolte tematické číslo časopisu</option>
                                <?php
                                foreach ($casopisResults as $casopis) :
                                ?>
                                    <option value="<?= $casopis["ID"]; ?>"><?= $casopis["TEMA"]; ?> (<?= $casopis["POCET_PRISPEVKU_ZAJEM"]; ?>/<?= $casopis["POCET_PRISPEVKU"]; ?>/<?= $casopis["MAX_POCET_PRISPEVKU"]; ?>)</option>
                                <?php
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="prispevekName">Název článku</label>
                            <input type="text" class="form-control" id="prispevekName" name="prispevekName" required>
                        </div>
                        <div class="col-md-12">
                            <label for="prispevekFile">Soubor článku</label>
                            <input type="file" class="form-control" id="prispevekFile" name="prispevekFile" accept="application/pdf,.doc,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                        </div>
                        <span>Spolautoři</span>
                        <ul id="coauthorsList" class="list-group list-group">

                        </ul>
                        <div class="col-md-12 d-grid gap-2 d-md-block">
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#searchAutorModal">Vyhledat autora</button>
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addAutorModal">Přidat nového autora</button>
                        </div>
                        <button type="submit" class="col-12 btn btn-primary">Vložit článek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modální okno pro přidání nového autora -->
<div class="modal fade" id="addAutorModal" tabindex="-1" aria-labelledby="addAutorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAutorModalLabel">Přidat nového autora</h5>
                <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#addPrispevekModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAutorForm">
                    <div class="mb-3">
                        <label for="jmeno" class="form-label">Jméno</label>
                        <input type="text" class="form-control" id="jmeno" required>
                    </div>
                    <div class="mb-3">
                        <label for="prijmeni" class="form-label">Příjmení</label>
                        <input type="text" class="form-control" id="prijmeni" required>
                    </div>
                    <div class="mb-3">
                        <label for="mail" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="mail" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Přidat autora</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modální okno pro vyhledání autora -->
<div class="modal fade" id="searchAutorModal" tabindex="-1" aria-labelledby="searchAutorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchAutorModalLabel">Vyhledat autora</h5>
                <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#addPrispevekModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="searchAutorForm">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="queryJmeno" placeholder="Jméno">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="queryPrijmeni" placeholder="Příjmení">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Vyhledat autora</button>
                    <div id="searchResults" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modální okno pro nahrání nové verze -->
<div class="modal fade" id="versionModal" tabindex="-1" aria-labelledby="versionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionModalLabel">Editace článku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addVerzeForm" enctype="multipart/form-data">
                    <input type="hidden" id="prispevekId" name="prispevekId">

                    <div class="mb-3">
                        <label for="prispevekName">Název článku</label>
                        <input type="text" class="form-control" id="prispevekName" name="prispevekName" required>
                    </div>

                    <div class="col-md-12">
                        <label for="prispevekFile">Soubor článku</label>
                        <input type="file" class="form-control" id="prispevekFile" name="prispevekFile" accept="application/pdf,.doc,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Uložit změny</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Předvyplnění modálního okna -->
<script>
    $(document).ready(function() {
        $('.open-modal').click(function() {
            var row = $(this).closest('tr');
            var id = row.find('td:eq(0)').text();
            var nazev = row.find('td:eq(1)').text();

            $('#versionModal #prispevekId').val(id);
            $('#versionModal #prispevekName').val(nazev);

            $('#versionModal').modal('show');
        });
    });

    $(document).ready(function() {
        $('#addVerzeForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: 'endpoints/prispevek_add_verze.php',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {

                    // Reloadni stránku s upozorněním, že byla vložena nová verze článku.
                    window.location.assign(`?version-success`);

                    // $('#versionModal').modal('hide');
                },
                error: function() {
                    alert('Došlo k chybě při aktualizaci dat');
                }
            });
        });
    });
</script>


<script>
    const loggedOsobaId = <?= SessionInfo::getLoggedOsoba()->getId(); ?>;

    // Množina ID spoluautorů
    const spoluautoriIds = new Set();

    $(document).ready(function() {
        const showAlert = message => {
            const alertDiv = $('<div>').addClass('alert alert-danger alert-dismissible fade show')
                .attr('role', 'alert')
                .html(message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
            $('#addPrispevekForm').prepend(alertDiv);

            // Zavři se za 3 sekundy
            setTimeout(function() {
                alertDiv.alert('close');
            }, 3000);
        };

        // Zpracování přidání nového příspěvku 
        $("#addPrispevekForm").submit(function() {
            event.preventDefault();

            // Vytvoř seznam všech autorů (včetně přihlášeného uživatele)
            authorIds = Array.from(spoluautoriIds);
            authorIds.push(loggedOsobaId);

            const fileName = $("#prispevekFile")[0].files[0].name;
            const fileExtension = fileName.split(".").pop().toLowerCase();

            if (!["pdf", "doc", "docx"].includes(fileExtension)) {
                showAlert("Souborem článku mohou být pouze soubory typu PDF či DOC(X).");
                return;
            }

            // Data pro odeslání AJAX požadavku
            const formData = new FormData($("#addPrispevekForm")[0]);
            formData.append("prispevekVersion", 1);
            formData.append("prispevekAuthorIds", authorIds);

            $.ajax({
                type: "POST",
                url: "endpoints/prispevek_add.php",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {

                    if (!response.success) {
                        console.error("Error:", response.message);
                        showAlert("Článek nebylo možné vytvořit.");
                        return;
                    }

                    // Reloadni stránku s upozorněním, že byl článek vložen.
                    window.location.assign(`?article-success-title=${formData.get("prispevekName")}`);

                    // $("#addPrispevekForm").trigger("reset");
                    // $("#addPrispevekModal").modal("hide");
                },
                error: function(error) {
                    console.error("Error:", error.responseText);
                    showAlert("Článek nebylo možné vytvořit.");
                }
            });
        });

        // Zpracování přidání nového autora 
        $("#addAutorForm").submit(function(event) {
            event.preventDefault();

            $.ajax({
                type: "POST",
                url: "endpoints/autor_add.php",
                data: {
                    mail: $("#mail").val(),
                    jmeno: $("#jmeno").val(),
                    prijmeni: $("#prijmeni").val(),
                    login: $("#login").val()
                },
                dataType: "json",
                success: function(response) {
                    $("#addAutorForm").trigger("reset");
                    $("#addAutorModal").modal("hide");
                    $("#addPrispevekModal").modal("show");
                },
                error: function(error) {
                    // Handle error response
                    console.error(error.responseText);
                }
            });
        });

        // Zpracování vyhledání autora 
        $("#searchAutorForm").submit(function(event) {
            event.preventDefault();
            // Send AJAX request to search for Osoba
            $.ajax({
                type: "POST",
                url: "endpoints/autor_search.php",
                data: {
                    queryJmeno: $("#queryJmeno").val(),
                    queryPrijmeni: $("#queryPrijmeni").val(),
                    idFilter: loggedOsobaId,
                },
                dataType: "json",
                success: function(response) {
                    displaySearchResults(response);
                },
                error: function(error) {
                    console.error("Error:", error.responseText);
                }
            });
        });

        const addCoauthor = (osoba) => {
            const selectedAutorList = $("#coauthorsList");
            const listItem = $(
                "<li class='list-group-item d-flex justify-content-between align-items-start'>" +
                "<div class='ms-2 me-auto'>" +
                "<div class='fw-bold'>" + osoba.JMENO + " " + osoba.PRIJMENI + "</div>" +
                osoba.MAIL +
                "</div>" +
                `<button class='btn btn-danger' data-osoba-id='${osoba.ID}'>-</button>` +
                "</li>");

            selectedAutorList.append(listItem);

            // Přidej click event pro tlačítko smazání
            listItem.find(".btn-danger").click(function() {
                const osobaId = $(this).data("osoba-id");

                spoluautoriIds.delete(osobaId);
                console.log("Odebírám ze seznamu autorů:", osobaId, Array.from(spoluautoriIds));

                listItem.remove();
            });
        };

        // Zobraz výsledky vyhledávání
        function displaySearchResults(results) {
            const searchResultsContainer = $("#searchResults");
            searchResultsContainer.empty();

            if (results.length > 0) {
                const autorList = $("<ul class='list-group'></ul>");

                results.forEach(function(osoba) {
                    // Vytvoř HTML pro každého autora
                    const listItem = $(
                        "<li class='list-group-item d-flex justify-content-between align-items-start'>" +
                        "<div class='ms-2 me-auto'>" +
                        "<div class='fw-bold'>" + osoba.JMENO + " " + osoba.PRIJMENI + "</div>" +
                        osoba.MAIL +
                        "</div>" +
                        `<button class='btn btn-success' data-osoba-id='${osoba.ID}' data-osoba-jmeno='${osoba.JMENO}' data-osoba-prijmeni='${osoba.PRIJMENI}' data-osoba-mail='${osoba.MAIL}'>+</button>` +
                        "</li>"
                    );

                    // Přidej HTML element pod seznam autorů
                    autorList.append(listItem);

                    // Přidej click event pro tlačítko smazání
                    listItem.find(".btn-success").click(function() {
                        const osobaId = $(this).data("osoba-id");

                        if (!spoluautoriIds.has(osobaId)) {
                            addCoauthor({
                                ID: $(this).data("osoba-id"),
                                JMENO: $(this).data("osoba-jmeno"),
                                PRIJMENI: $(this).data("osoba-prijmeni"),
                                MAIL: $(this).data("osoba-mail")
                            });

                            spoluautoriIds.add(osobaId);
                            console.log("Přidávám do seznamu autorů:", osobaId, Array.from(spoluautoriIds));
                        }

                        searchResultsContainer.empty();
                        $("#searchAutorForm").trigger("reset");
                        $("#searchAutorModal").modal("hide");
                        $("#addPrispevekModal").modal("show");
                    });
                });

                searchResultsContainer.append(autorList);
            } else {
                searchResultsContainer.html("<p>No results found.</p>");
            }
        }
    });
</script>