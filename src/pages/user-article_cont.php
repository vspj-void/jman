<?php

require_once "classes/osoba.php";
require_once "includes/db_connect.php";

require_once "classes/session_info.php";

$mysqli = DbConnect::connect();

$query = "SELECT C.*, 0 AS `POCET_PRISPEVKU`, COUNT(P.ID) AS `POCET_PRISPEVKU_ZAJEM` FROM CASOPIS AS C
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
                  WHERE O.ID = " . SessionInfo::getLoggedOsoba()->getId();

// Pokud je zadán vyhledávací termín
if (!empty($searchTerm)) {
    $queryArticles .= " AND PV.NAZEV LIKE '%$searchTerm%'";
}

$mysqli = DbConnect::connect();
$queryArticles .= " ORDER BY $sortBy $sortOrder LIMIT $offset, $articlesPerPage";

$resultArticles = $mysqli->query($queryArticles);

?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrispevekModal">Nový článek</button>
        </div>
        <div class="col-md-6">
            <form method="GET" action="?page=<?= $page; ?>" class="row input-group mb-3">
                <div class="col-6"> <!--šířka vyhledávacího pole-->
                    <input type="text" class="form-control" id="searchInput" name="search" placeholder="Zadejte název článku">
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
                <th scope="col"><a href="?page=<?= $page; ?>&sort=AUTOR_PRIJMENI&order=<?= ($sortBy === 'AUTOR_PRIJMENI' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Autor</a></th>
                <th scope="col"><a href="?page=<?= $page; ?>&sort=TEMA&order=<?= ($sortBy === 'TEMA' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?>">Téma časopisu</a></th>
                <th scope="col">Otevřít článek</th>
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

                    $("#addPrispevekForm").trigger("reset");
                    $("#addPrispevekModal").modal("hide");
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
                    queryPrijmeni: $("#queryPrijmeni").val()
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
                        `<button class='btn btn-success' data-osoba-id='${osoba.ID}'>+</button>` +
                        "</li>"
                    );

                    // Přidej HTML element pod seznam autorů
                    autorList.append(listItem);

                    // Přidej click event pro tlačítko smazání
                    listItem.find(".btn-success").click(function() {
                        const osobaId = $(this).data("osoba-id");
                        spoluautoriIds.add(osobaId);
                        console.log("Seznam autorů:", Array.from(spoluautoriIds));
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