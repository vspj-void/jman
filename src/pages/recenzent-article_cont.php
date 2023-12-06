<?php
require_once "includes/db_connect.php";
require_once "classes/profil.php";
?>

<div class="container mt-4">
    <div class="jumbotron">
        <h1 class="display-4">Články k recenzi</h1>
        <hr class="my-4">
    </div>
</div>

<!-- Tlačítko pro vytvoření recenze -->
<div class="container mt-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReviewModal">
        Vytvořit recenzi
    </button>
</div>

<!-- Modální okno pro vytvoření recenze -->
<div class="modal fade" id="createReviewModal" tabindex="-1" aria-labelledby="createReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createReviewModalLabel">Vytvořit recenzi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div>(1 - nejlepší, 5 - nejhorší)</div><br>
                <!-- Formulář pro vytvoření recenze -->
                <form>
                    <div class="mb-3">
                        <label for="ratingRelevance" class="form-label">Aktuálnost</label>
                        <select class="form-select" id="ratingRelevance" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3"> 
                        <label for="ratingInterest" class="form-label">Zajímavost a přínosnost</label>
                        <select class="form-select" id="ratingInterest" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ratingOriginality" class="form-label">Originalita a odborná úroveň</label>
                        <select class="form-select" id="ratingOriginality" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ratingLanguage" class="form-label">Jazyková a stylistická úroveň</label>
                        <select class="form-select" id="ratingLanguage" required>
                            <option value="" selected disabled>Vyberte hodnocení</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="additionalComments" class="form-label">Přidat komentář</label>
                        <textarea class="form-control" id="additionalComments" rows="3"></textarea>
                    </div>

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zavřít</button>
                    <button type="button" class="btn btn-primary">Uložit recenzi</button>
                </form>
            </div>
        </div>
    </div>
</div>
