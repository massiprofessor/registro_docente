<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';

if (!isset($_GET['studente'], $_GET['classe'])) {
    die("Errore: parametri mancanti.");
}

$studente_id = (int)$_GET['studente'];
$classe_id   = (int)$_GET['classe'];

// Dettagli studente
$stmt = $conn->prepare("
    SELECT s.Nome, s.Cognome, c.Nome AS Classe, s.Commento
    FROM studenti s
    INNER JOIN classi c ON s.ID_Classe = c.ID_Classe
    WHERE s.ID_Studente = :id
");
$stmt->execute([':id' => $studente_id]);
$studente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studente) die("Errore: studente non trovato.");

// Ore frequentate
$stmt_ore = $conn->prepare("
    SELECT 
        SUM(CASE WHEN p.Presenza_Mattina = 1 THEN ic.Ore_Mattina ELSE 0 END) +
        SUM(CASE WHEN p.Presenza_Pomeriggio = 1 THEN ic.Ore_Pomeriggio ELSE 0 END) AS Ore_Frequentate
    FROM presenze p
    INNER JOIN impostazioni_classe ic ON p.ID_Classe = ic.ID_Classe
    WHERE p.ID_Studente = :sid AND p.ID_Classe = :cid
");
$stmt_ore->execute([':sid' => $studente_id, ':cid' => $classe_id]);
$ore_frequentate = $stmt_ore->fetchColumn() ?? 0;

// Gestione commento
$editing_commento = isset($_GET['edit_commento']) && $_GET['edit_commento'] === '1';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commento'])) {
    $commento = trim($_POST['commento']);
    $stmt_upd = $conn->prepare("UPDATE studenti SET Commento = :c WHERE ID_Studente = :id");
    if ($stmt_upd->execute([':c' => $commento, ':id' => $studente_id])) {
        $studente['Commento'] = $commento;
        $success = "Commento aggiornato con successo.";
        $editing_commento = false;
    } else {
        $error = "Errore durante l'aggiornamento del commento.";
    }
}

// Voti
$stmt_voti = $conn->prepare("
    SELECT m.Materia, v.Voto, v.Data
    FROM voti v
    INNER JOIN materie m ON v.ID_Materia = m.ID_Materia
    WHERE v.ID_Studente = :id
    ORDER BY m.Materia ASC, v.Data ASC
");
$stmt_voti->execute([':id' => $studente_id]);
$voti = $stmt_voti->fetchAll(PDO::FETCH_ASSOC);

// Dati grafico
$grafico_dati = [];
foreach ($voti as $voto) {
    $grafico_dati[$voto['Materia']][] = $voto['Voto'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheda Alunno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary">Scheda Alunno</h1>
            <a href="gestione_voti.php?classe=<?= $classe_id ?>" class="btn btn-outline-secondary">Torna alla Classe</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($studente['Nome'] . ' ' . $studente['Cognome']) ?></h5>
                <p class="card-text"><strong>Classe:</strong> <?= htmlspecialchars($studente['Classe']) ?></p>
                <p class="card-text"><strong>Ore Frequentate:</strong> <?= (int)$ore_frequentate ?></p>

                <?php if ($editing_commento): ?>
                    <form method="POST" class="mt-3">
                        <label for="commento" class="form-label"><strong>Modifica Commento:</strong></label>
                        <textarea name="commento" id="commento" rows="3" class="form-control"><?= htmlspecialchars($studente['Commento'] ?? '') ?></textarea>
                        <button type="submit" class="btn btn-primary mt-2">Salva Commento</button>
                        <a href="scheda_alunno.php?studente=<?= $studente_id ?>&classe=<?= $classe_id ?>" class="btn btn-secondary mt-2">Annulla</a>
                    </form>
                <?php else: ?>
                    <p><strong>Commento:</strong> <?= htmlspecialchars($studente['Commento'] ?? 'Nessun commento.') ?></p>
                    <a href="scheda_alunno.php?studente=<?= $studente_id ?>&classe=<?= $classe_id ?>&edit_commento=1" class="btn btn-sm btn-primary">Modifica Commento</a>
                <?php endif; ?>

                <?php if ($success): ?><div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($error):   ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            </div>
        </div>

        <h2>Andamento Voti</h2>
        <?php if (!empty($grafico_dati)): ?>
            <div class="mb-4"><canvas id="graficoVoti"></canvas></div>
            <script>
                const ctx = document.getElementById('graficoVoti').getContext('2d');
                const graficoDati = <?= json_encode($grafico_dati, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                const labels   = Object.keys(graficoDati);
                const datasets = labels.map(function(materia) {
                    return {
                        label: materia,
                        data: graficoDati[materia],
                        backgroundColor: 'rgba(' + Math.floor(Math.random()*200+55) + ',' + Math.floor(Math.random()*200+55) + ',' + Math.floor(Math.random()*200+55) + ',0.7)'
                    };
                });
                new Chart(ctx, {
                    type: 'bar',
                    data: { labels: labels, datasets: datasets },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'top' } },
                        scales: {
                            x: { title: { display: true, text: 'Materie' } },
                            y: { beginAtZero: true, max: 100, title: { display: true, text: 'Voti' } }
                        }
                    }
                });
            </script>
        <?php else: ?>
            <p class="text-muted">Nessun voto disponibile per questo studente.</p>
        <?php endif; ?>
    </div>
</body>
</html>
