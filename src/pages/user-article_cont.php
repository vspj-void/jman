<?php

require_once "classes/osoba.php";
require_once "includes/db_connect.php";

$query = "SELECT * FROM CASOPIS";
$result = $mysqli->query($query);

if (!$result) {
    die("Failed to query casopis.");
}

$casopisResults = $result->fetch_all(MYSQLI_ASSOC);

?>

<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrispevekModal">Nový článek</button>

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
                            <label for="prispevekCasopisId">Téma</label>
                            <select class="form-select" name="prispevekCasopisId" id="prispevekCasopisId">
                                <?php
                                foreach ($casopisResults as $casopis) :
                                ?>
                                    <option value="<?= $casopis["ID"]; ?>"><?= $casopis["TEMA"]; ?></option>
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
                            <input type="file" class="form-control" id="prispevekFile" name="prispevekFile" required>
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
    const loggedOsobaId = <?= Osoba::getLoggedOsoba()->getId(); ?>;

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

            if (fileExtension !== "pdf") {
                showAlert("Souborem článku může být pouze soubor typu PDF.");
                return;
            }

            // Data pro odeslání AJAX požadavku
            const formData = new FormData($("#addPrispevekForm")[0]);
            formData.append("prispevekVersion", 1);
            formData.append("prispevekCasopisId", 1);
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