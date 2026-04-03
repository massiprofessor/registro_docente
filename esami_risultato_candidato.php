<?php
session_start();

if (!isset($_SESSION['esame_sessione_id'])) {
    header("Location: esami_accesso.php");
    exit;
}

require_once 'db_connection.php';

$sessione_id = (int)$_SESSION['esame_sessione_id'];

// Carica sessione
$stmtSess = $conn->prepare("
    SELECT es.*, e.titolo, e.num_domande, e.ID_Classe, c.Nome AS classe_nome
    FROM esami_sessioni es
    JOIN esami e ON es.esame_id = e.id
    JOIN classi c ON e.ID_Classe = c.ID_Classe
    WHERE es.id = :id
");
$stmtSess->execute([':id' => $sessione_id]);
$sessione = $stmtSess->fetch(PDO::FETCH_ASSOC);

if (!$sessione || !$sessione['completato']) {
    header("Location: esami_accesso.php");
    exit;
}

// Carica domande con risposte e risposta data
$stmtDom = $conn->prepare("SELECT * FROM esami_domande WHERE esame_id = :e ORDER BY ordine");
$stmtDom->execute([':e' => $sessione['esame_id']]);
$domande = $stmtDom->fetchAll(PDO::FETCH_ASSOC);

// Risposte date
$stmtRC = $conn->prepare("SELECT domanda_id, risposta_id FROM esami_risposte_candidati WHERE sessione_id = :s");
$stmtRC->execute([':s' => $sessione_id]);
$risposte_date = [];
foreach ($stmtRC->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $risposte_date[$r['domanda_id']] = $r['risposta_id'];
}

foreach ($domande as &$dom) {
    $stmtRis = $conn->prepare("SELECT * FROM esami_risposte WHERE domanda_id = :d ORDER BY lettera");
    $stmtRis->execute([':d' => $dom['id']]);
    $dom['risposte'] = $stmtRis->fetchAll(PDO::FETCH_ASSOC);
    $dom['risposta_data_id'] = $risposte_date[$dom['id']] ?? null;
}
unset($dom);

$punteggio  = $sessione['punteggio'];
$tot        = count($domande);
$corrette   = 0;
foreach ($domande as $dom) {
    foreach ($dom['risposte'] as $r) {
        if ($r['corretta'] && $r['id'] == $dom['risposta_data_id']) {
            $corrette++;
            break;
        }
    }
}

// Colore voto
$voto_class = $punteggio >= 60 ? 'success' : ($punteggio >= 40 ? 'warning' : 'danger');
$voto_label = $punteggio >= 90 ? 'Ottimo!' : ($punteggio >= 75 ? 'Buono' : ($punteggio >= 60 ? 'Sufficiente' : 'Insufficiente'));

// Pulisce sessione esame (mantiene login docente se c'è)
unset($_SESSION['esame_sessione_id'], $_SESSION['esame_id'], $_SESSION['candidato_nome'],
      $_SESSION['esame_punteggio'], $_SESSION['esame_corrette'], $_SESSION['esame_tot']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risultato Esame</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .score-circle {
            width: 160px; height: 160px; border-radius: 50%;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: 800;
            border: 8px solid;
        }
        .score-success { border-color: #198754; color: #198754; }
        .score-warning { border-color: #ffc107; color: #856404; }
        .score-danger  { border-color: #dc3545; color: #dc3545; }
        .risposta-corretta-bg { background: #d1e7dd; border-left: 4px solid #198754; border-radius: 6px; }
        .risposta-errata-bg   { background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 6px; }
        .risposta-neutra-bg   { background: #f8f9fa; border-left: 4px solid #dee2e6; border-radius: 6px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">🎓 Accademia del Levante — Risultato Esame</span>
    </div>
</nav>

<div class="container pb-5">

    <!-- ── RIEPILOGO PUNTEGGIO ─────────────────────────────── -->
    <div class="card shadow text-center p-4 mb-4">
        <h4 class="mb-1"><?= htmlspecialchars($sessione['titolo']) ?></h4>
        <p class="text-muted mb-4"><?= htmlspecialchars($sessione['nome_candidato'] . ' ' . $sessione['cognome_candidato']) ?> — <?= htmlspecialchars($sessione['classe_nome']) ?></p>

        <div class="d-flex justify-content-center mb-3">
            <div class="score-circle score-<?= $voto_class ?>">
                <span><?= number_format($punteggio, 0) ?>%</span>
                <small style="font-size:.75rem; font-weight:500"><?= $voto_label ?></small>
            </div>
        </div>

        <p class="fs-5 mb-0">
            Risposte corrette: <strong><?= $corrette ?> / <?= $tot ?></strong>
        </p>
        <p class="text-muted">
            Completato il <?= date('d/m/Y H:i', strtotime($sessione['terminato_il'])) ?>
        </p>

        <a href="esami_accesso.php" class="btn btn-outline-secondary mt-2">← Torna alla schermata di accesso</a>
    </div>

    <!-- ── DETTAGLIO RISPOSTE ──────────────────────────────── -->
    <h5 class="mb-3">📋 Riepilogo Risposte</h5>

    <?php foreach ($domande as $idx => $dom): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="fw-bold mb-3">D<?= $dom['ordine'] ?>. <?= htmlspecialchars($dom['testo']) ?></p>
            <?php foreach ($dom['risposte'] as $r):
                $data_id = $dom['risposta_data_id'];
                $is_sel  = ($r['id'] == $data_id);
                $is_corr = $r['corretta'];

                if ($is_corr && $is_sel)       $cls = 'risposta-corretta-bg';
                elseif (!$is_corr && $is_sel)  $cls = 'risposta-errata-bg';
                elseif ($is_corr && !$is_sel)  $cls = 'risposta-corretta-bg';
                else                           $cls = 'risposta-neutra-bg';
            ?>
            <div class="<?= $cls ?> p-2 mb-1 d-flex align-items-center gap-2">
                <?php if ($is_corr && $is_sel):   ?><span>✅</span>
                <?php elseif (!$is_corr && $is_sel): ?><span>❌</span>
                <?php elseif ($is_corr):          ?><span>☑️</span>
                <?php else:                       ?><span>⬜</span>
                <?php endif; ?>
                <strong><?= $r['lettera'] ?>.</strong> <?= htmlspecialchars($r['testo']) ?>
                <?php if ($is_sel): ?><em class="ms-2 text-muted">(tua risposta)</em><?php endif; ?>
                <?php if ($is_corr): ?><em class="ms-auto text-success fw-bold">← corretta</em><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$dom['risposta_data_id']): ?>
                <div class="alert alert-warning py-1 mt-2 mb-0">⚠️ Nessuna risposta data</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>
</body>
</html>
