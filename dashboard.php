<?php
session_start(); // Avvia la sessione

// Controlla se l'utente è loggato
if (!isset($_SESSION['User_id'])) {
    // Reindirizza al login se non è loggato
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .card img {
            width: 50px;
            height: 50px;
        }
        .dashboard .card {
            transition: transform 0.2s;
        }
        .dashboard .card:hover {
            transform: scale(1.05);
        }
		.navbar-brand img {
        border-radius: 50%; /* Logo rotondo */
    }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <span>Dashboard - Accademia del Levante</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Benvenuto, <?php echo $_SESSION['username']; ?>!</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container py-5">
        <div class="text-center mb-4">
            <h1 class="mb-3">Benvenuto, <?php echo $_SESSION['username']; ?>!</h1>
            <p class="text-muted"><?php echo "Oggi è " . date('Y-m-d'); ?></p>
        </div>

        <div class="row g-4 dashboard">
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='alunni_classi.php';" style="cursor: pointer;">
                    <img src="icons/alunni.png" alt="Alunni" class="mx-auto">
                    <h5 class="mt-3">Alunni</h5>
                    <p class="text-muted">Visualizza e inserisci alunni in una classe</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='gestione_classi.php';" style="cursor: pointer;">
                    <img src="icons/classi.png" alt="Classi" class="mx-auto">
                    <h5 class="mt-3">Classi</h5>
                    <p class="text-muted">Visualizza e inserisci classi</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='gestione_voti.php';" style="cursor: pointer;">
                    <img src="icons/voti.png" alt="Voti" class="mx-auto">
                    <h5 class="mt-3">Voti</h5>
                    <p class="text-muted">Visualizza e inserisci voti</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='presenze.php';" style="cursor: pointer;">
                    <img src="icons/presenze.png" alt="Presenze" class="mx-auto">
                    <h5 class="mt-3">Presenze</h5>
                    <p class="text-muted">Visualizza e inserisci presenze</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='materie.php';" style="cursor: pointer;">
                    <img src="icons/materie.png" alt="Materie" class="mx-auto">
                    <h5 class="mt-3">Materie</h5>
                    <p class="text-muted">Lista e inserimento materie</p>
                </div>
            </div>
			<div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='scheduling.php';" style="cursor: pointer;">
                    <img src="icons/scheduling.png" alt="Scheduling" class="mx-auto">
                    <h5 class="mt-3">Scheduling</h5>
                    <p class="text-muted">Programma lo scheduling delle tue lezioni</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3" onclick="location.href='esami.php';" style="cursor: pointer;">
                    <div class="mx-auto d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-size:2rem;">📝</div>
                    <h5 class="mt-3">Esami / Quiz</h5>
                    <p class="text-muted">Crea e gestisci esami a risposta multipla</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-3 bg-danger text-white" onclick="location.href='logout.php';" style="cursor: pointer;">
                    <img src="icons/logout.png" alt="Logout" class="mx-auto">
                    <h5 class="mt-3">Logout</h5>
                    <p class="text-muted">Uscire dall'applicazione</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
