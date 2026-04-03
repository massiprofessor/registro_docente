<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db_connection.php';

$user_id = $_SESSION['User_id'];
$stmtRoot = $conn->prepare("SELECT root FROM docente WHERE ID_Docente = :id");
$stmtRoot->execute([':id' => $user_id]);
$docente = $stmtRoot->fetch(PDO::FETCH_ASSOC);
if ($docente['root'] !== 'SI') {
    header("Location: access_denied.php");
    exit;
}

$msg_success = '';
$msg_error   = '';
$esame       = null;
$domande     = [];

// ── CREA NUOVO ESAME (da esami.php) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crea_esame'])) {
    $titolo       = trim($_POST['titolo']);
    $classe_id    = (int)$_POST['classe_id'];
    $materia_id   = $_POST['materia_id'] ? (int)$_POST['materia_id'] : null;
    $num_domande  = (int)$_POST['num_domande'];
    $durata       = (int)$_POST['durata_minuti'];

    $stmt = $conn->prepare("INSERT INTO esami (titolo, ID_Classe, ID_Materia, num_domande, durata_minuti, creato_da) VALUES (:t,:c,:m,:n,:d,:u)");
    $stmt->execute([':t' => $titolo, ':c' => $classe_id, ':m' => $materia_id, ':n' => $num_domande, ':d' => $durata, ':u' => $user_id]);
    $esame_id = $conn->lastInsertId();
    header("Location: esami_crea.php?esame_id=$esame_id&nuovo=1");
    exit;
}

// Recupera GET anche se arrivo da esami.php tramite form action
if (!isset($_GET['esame_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titolo'])) {
    // Gestito sopra
}

// ── AGGIUNGI DOMANDA ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_domanda'])) {
    $esame_id   = (int)$_POST['esame_id'];
    $testo_dom  = trim($_POST['testo_domanda']);
    $risposte   = $_POST['risposte'];
    $corretta   = (int)$_POST['corretta']; // indice 0-based

    // Conta domande esistenti
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM esami_domande WHERE esame_id = :e");
    $stmtCount->execute([':e' => $esame_id]);
    $ordine = $stmtCount->fetchColumn() + 1;

    $stmtD = $conn->prepare("INSERT INTO esami_domande (esame_id, testo, ordine) VALUES (:e,:t,:o)");
    $stmtD->execute([':e' => $esame_id, ':t' => $testo_dom, ':o' => $ordine]);
    $dom_id = $conn->lastInsertId();

    $lettere = ['A','B','C','D'];
    foreach ($risposte as $i => $r_testo) {
        if (trim($r_testo) === '') continue;
        $stmtR = $conn->prepare("INSERT INTO esami_risposte (domanda_id, testo, corretta, lettera) VALUES (:d,:t,:c,:l)");
        $stmtR->execute([':d' => $dom_id, ':t' => trim($r_testo), ':c' => ($i === $corretta ? 1 : 0), ':l' => $lettere[$i]]);
    }
    $msg_success = "Domanda aggiunta!";
    header("Location: esami_crea.php?esame_id=$esame_id&ok=1");
    exit;
}

// ── ELIMINA DOMANDA ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_domanda'])) {
    $esame_id  = (int)$_POST['esame_id'];
    $domanda_id = (int)$_POST['domanda_id'];
    $conn->prepare("DELETE FROM esami_domande WHERE id = :id")->execute([':id' => $domanda_id]);
    header("Location: esami_crea.php?esame_id=$esame_id");
    exit;
}

// ── MODIFICA ESAME (titolo/durata/num_domande) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_impostazioni'])) {
    $esame_id    = (int)$_POST['esame_id'];
    $titolo      = trim($_POST['titolo']);
    $num_domande = (int)$_POST['num_domande'];
    $durata      = (int)$_POST['durata_minuti'];
    $conn->prepare("UPDATE esami SET titolo=:t, num_domande=:n, durata_minuti=:d WHERE id=:id")
         ->execute([':t' => $titolo, ':n' => $num_domande, ':d' => $durata, ':id' => $esame_id]);
    $msg_success = "Impostazioni salvate.";
    header("Location: esami_crea.php?esame_id=$esame_id&ok=1");
    exit;
}

// ── CARICA ESAME ─────────────────────────────────────────────────────────────
if (isset($_GET['esame_id'])) {
    $esame_id = (int)$_GET['esame_id'];
    $stmtE = $conn->prepare("SELECT e.*, c.Nome AS classe_nome, m.Materia AS materia_nome FROM esami e JOIN classi c ON e.ID_Classe=c.ID_Classe LEFT JOIN materie m ON e.ID_Materia=m.ID_Materia WHERE e.id=:id");
    $stmtE->execute([':id' => $esame_id]);
    $esame = $stmtE->fetch(PDO::FETCH_ASSOC);

    if (!$esame) {
        header("Location: esami.php");
        exit;
    }

    // Carica domande con risposte
    $stmtDom = $conn->prepare("SELECT * FROM esami_domande WHERE esame_id = :e ORDER BY ordine");
    $stmtDom->execute([':e' => $esame_id]);
    $domande_raw = $stmtDom->fetchAll(PDO::FETCH_ASSOC);

    foreach ($domande_raw as $dom) {
        $stmtRis = $conn->prepare("SELECT * FROM esami_risposte WHERE domanda_id = :d ORDER BY lettera");
        $stmtRis->execute([':d' => $dom['id']]);
        $dom['risposte'] = $stmtRis->fetchAll(PDO::FETCH_ASSOC);
        $domande[] = $dom;
    }
}

if (isset($_GET['ok'])) $msg_success = "Operazione completata!";
if (isset($_GET['nuovo'])) $msg_success = "Esame creato! Ora inserisci le domande.";

$tot_dom = count($domande);
$mancanti = $esame ? max(0, $esame['num_domande'] - $tot_dom) : 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Esame</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .domanda-card { border-left: 4px solid #0d6efd; }
        .risposta-corretta { background: #d1e7dd; border-radius: 6px; padding: 2px 8px; }
        .risposta-errata { background: #f8d7da; border-radius: 6px; padding: 2px 8px; }
        .progress-header { height: 8px; border-radius: 4px; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">✏️ Editor Esame</span>
        <a href="esami.php" class="btn btn-outline-light btn-sm">← Torna agli Esami</a>
    </div>
</nav>

<div class="container pb-5">

<?php if (!$esame): ?>
<!-- Form creazione (chiamato da esami.php) -->
<div class="alert alert-warning">Esame non trovato. <a href="esami.php">Torna indietro</a>.</div>
<?php else: ?>

    <?php if ($msg_success): ?>
        <div class="alert alert-success alert-dismissible">
            <?= $msg_success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── HEADER ESAME ─────────────────────────────────────── -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4><?= htmlspecialchars($esame['titolo']) ?></h4>
                    <p class="mb-1 text-muted">
                        Classe: <strong><?= htmlspecialchars($esame['classe_nome']) ?></strong>
                        <?= $esame['materia_nome'] ? ' | Materia: <strong>'.htmlspecialchars($esame['materia_nome']).'</strong>' : '' ?>
                        | ⏱ <strong><?= $esame['durata_minuti'] ?> min</strong>
                    </p>
                    <div class="progress mt-2" style="height:8px; width:300px;">
                        <div class="progress-bar <?= $tot_dom >= $esame['num_domande'] ? 'bg-success' : 'bg-warning' ?>"
                             style="width:<?= min(100, round($tot_dom/$esame['num_domande']*100)) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $tot_dom ?> / <?= $esame['num_domande'] ?> domande inserite
                        <?= $mancanti > 0 ? "($mancanti mancanti)" : '<span class="text-success">✓ Completo</span>' ?>
                    </small>
                </div>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseImpostazioni">
                    ⚙️ Impostazioni
                </button>
            </div>

            <!-- Modifica impostazioni -->
            <div class="collapse mt-3" id="collapseImpostazioni">
                <form method="POST" class="row g-2">
                    <input type="hidden" name="esame_id" value="<?= $esame['id'] ?>">
                    <div class="col-md-5">
                        <label class="form-label">Titolo</label>
                        <input type="text" name="titolo" class="form-control form-control-sm" value="<?= htmlspecialchars($esame['titolo']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N. Domande previste</label>
                        <input type="number" name="num_domande" class="form-control form-control-sm" value="<?= $esame['num_domande'] ?>" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Durata (min)</label>
                        <input type="number" name="durata_minuti" class="form-control form-control-sm" value="<?= $esame['durata_minuti'] ?>" min="1" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button name="salva_impostazioni" class="btn btn-primary btn-sm w-100">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── AGGIUNGI DOMANDA ─────────────────────────────────── -->
    <?php if ($mancanti > 0 || true): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-bold">➕ Aggiungi Domanda <?= $tot_dom + 1 ?></div>
        <div class="card-body">
            <form method="POST" id="formDomanda">
                <input type="hidden" name="esame_id" value="<?= $esame['id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold">Testo della Domanda</label>
                    <textarea name="testo_domanda" class="form-control" rows="3" required placeholder="Scrivi qui la domanda..."></textarea>
                </div>

                <p class="fw-bold mb-2">Opzioni di risposta <span class="text-muted fw-normal">(seleziona la risposta corretta)</span></p>
                <div id="risposte-container">
                    <?php foreach (['A','B','C','D'] as $i => $let): ?>
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input class="form-check-input me-1" type="radio" name="corretta" value="<?= $i ?>" <?= $i===0?'checked':'' ?> required>
                            <strong><?= $let ?></strong>
                        </div>
                        <input type="text" name="risposte[]" class="form-control" required placeholder="Risposta <?= $let ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="aggiungi_domanda" class="btn btn-success">Aggiungi Domanda</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── DOMANDE ESISTENTI ────────────────────────────────── -->
    <?php if ($domande): ?>
    <h5 class="mb-3">📋 Domande Inserite (<?= $tot_dom ?>)</h5>
    <?php foreach ($domande as $idx => $dom): ?>
    <div class="card shadow-sm mb-3 domanda-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <p class="fw-bold mb-2">D<?= $dom['ordine'] ?>. <?= htmlspecialchars($dom['testo']) ?></p>
                    <div class="row">
                    <?php foreach ($dom['risposte'] as $r): ?>
                        <div class="col-md-6 mb-1">
                            <span class="<?= $r['corretta'] ? 'risposta-corretta' : 'risposta-errata' ?>">
                                <?= $r['corretta'] ? '✅' : '❌' ?>
                                <strong><?= $r['lettera'] ?>.</strong> <?= htmlspecialchars($r['testo']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <form method="POST" class="ms-3" onsubmit="return confirm('Eliminare questa domanda?')">
                    <input type="hidden" name="esame_id" value="<?= $esame['id'] ?>">
                    <input type="hidden" name="domanda_id" value="<?= $dom['id'] ?>">
                    <button name="elimina_domanda" class="btn btn-outline-danger btn-sm">🗑️</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
