<header class="mb-4">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Časopis Křídlo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="user-article.php">Moje články</a>
                    </li>
                </ul>
                <form class="d-flex">
                    <?php if (isset($_SESSION["username"])) : ?>
                        <span class="navbar-text">
                            Přihlášený uživatel: <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </span>
                        <a href="endpoints/logout.php" class="btn btn-secondary">Odhlásit se</a>
                    <?php else : ?>
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#loginModal">Přihlášení</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </nav>
    <div class="container-fluid text-center" style="background-color: #ffafaf;">
        <p class="text-muted">Tato aplikace je výsledkem školního projektu v kurzu Řízení SW projektů na Vysoké škole polytechnické Jihlava. Nejedná se o stránky skutečného odborného časopisu!</p>
    </div>
</header>
<!-- Modální okno pro přihlášení -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Přihlášení</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="username">Uživatelské jméno (j:testuser h:testuser)</label>
                            <input type="text" class="form-control" id="username" placeholder="Uživatelské jméno" required>
                        </div>
                        <div class="col-md-12">
                            <label for="password">Heslo</label>
                            <input type="password" class="form-control" id="password" placeholder="Heslo" required>
                        </div>
                        <button type="submit" class="col-12 btn btn-primary">Přihlásit se</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Skript pro přihlášení -->
<script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var username = document.getElementById('username').value;
        var password = document.getElementById('password').value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'endpoints/login.php', true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status == 200) {
                var response = JSON.parse(this.responseText);
                if (response.status == 'success') {
                    location.reload();
                } else {
                    alert('Přihlašovací údaje jsou nesprávné.');
                }
            }
        };
        xhr.send('username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
    });
</script>