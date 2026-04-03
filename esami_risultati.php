<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db_connection.php';

$user_id  = $_SESSION['User_id'];

// Verifica root
$stmtRoot = $conn->prepare("SELECT root FROM docente WHERE ID_Docente = :id");
$stmtRoot->execute([':id' => $user_id]);
$docente = $stmtRoot->fetch(PDO::FETCH_ASSOC);
if (!$docente || $docente['root'] !== 'SI') {
    header("Location: access_denied.php");
    exit;
}

if (!isset($_GET['esame_id'])) {
    header("Location: esami.php");
    exit;
}

$esame_id = (int)$_GET['esame_id'];

// Carica esame
$stmtE = $conn->prepare("SELECT e.*, c.Nome AS classe_nome, m.Materia AS materia_nome
    FROM esami e
    JOIN classi c ON e.ID_Classe = c.ID_Classe
    LEFT JOIN materie m ON e.ID_Materia = m.ID_Materia
    WHERE e.id = :id");
$stmtE->execute([':id' => $esame_id]);
$esame = $stmtE->fetch(PDO::FETCH_ASSOC);

if (!$esame) {
    header("Location: esami.php");
    exit;
}

// Carica domande
$stmtDom = $conn->prepare("SELECT * FROM esami_domande WHERE esame_id = :e ORDER BY ordine");
$stmtDom->execute([':e' => $esame_id]);
$domande = $stmtDom->fetchAll(PDO::FETCH_ASSOC);

// Carica risposte per ogni domanda
foreach ($domande as &$dom) {
    $stmtRis = $conn->prepare("SELECT * FROM esami_risposte WHERE domanda_id = :d ORDER BY lettera");
    $stmtRis->execute([':d' => $dom['id']]);
    $dom['risposte'] = $stmtRis->fetchAll(PDO::FETCH_ASSOC);
}
unset($dom);

// Carica sessioni — query semplificata senza ORDER BY su nullable
$stmtSess = $conn->prepare("SELECT * FROM esami_sessioni WHERE esame_id = :e");
$stmtSess->execute([':e' => $esame_id]);
$sessioni_raw = $stmtSess->fetchAll(PDO::FETCH_ASSOC);

// Per ogni sessione: abbina studente + carica risposte
$sessioni = [];
foreach ($sessioni_raw as $sess) {
    // Cerca studente abbinato per nome/cognome nella classe
    $stmtStu = $conn->prepare("SELECT ID_Studente FROM studenti
        WHERE Nome = :n AND Cognome = :c AND ID_Classe = :cl LIMIT 1");
    $stmtStu->execute([
        ':n'  => $sess['nome_candidato'],
        ':c'  => $sess['cognome_candidato'],
        ':cl' => $esame['ID_Classe']
    ]);
    $stu = $stmtStu->fetch(PDO::FETCH_ASSOC);
    $sess['ID_Studente'] = $stu ? $stu['ID_Studente'] : null;

    // Risposte date
    $stmtRC = $conn->prepare("SELECT domanda_id, risposta_id FROM esami_risposte_candidati WHERE sessione_id = :s");
    $stmtRC->execute([':s' => $sess['id']]);
    $sess['risposte'] = [];
    foreach ($stmtRC->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $sess['risposte'][(int)$r['domanda_id']] = (int)$r['risposta_id'];
    }

    $sessioni[] = $sess;
}

// Ordina: completati per punteggio DESC, poi in corso
usort($sessioni, function($a, $b) {
    if ($a['completato'] && $b['completato']) {
        return $b['punteggio'] - $a['punteggio'];
    }
    return $b['completato'] - $a['completato'];
});

// Statistiche
$completati      = [];
$voti_arr        = [];
foreach ($sessioni as $s) {
    if ($s['completato']) {
        $completati[] = $s;
        $voti_arr[]   = (float)$s['punteggio'];
    }
}
$media_punteggio = count($voti_arr) ? array_sum($voti_arr) / count($voti_arr) : 0;
$promossi = 0;
foreach ($voti_arr as $v) { if ($v >= 60) $promossi++; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risultati — <?= htmlspecialchars($esame['titolo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .punteggio-badge { font-size:1.1rem; font-weight:700; padding:4px 14px; border-radius:20px; }
        .risposta-corretta-sm { color:#198754; }
        .risposta-errata-sm   { color:#dc3545; }
        .dettaglio-collapse td { font-size:.87rem; }
        .stat-card { border-radius:12px; }
        .abbinato { color:#0d6efd; font-weight:600; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">📊 Dashboard Risultati</span>
        <div>
            <a href="esami.php" class="btn btn-outline-light btn-sm me-2">← Esami</a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <!-- HEADER ESAME -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4 class="mb-1"><?= htmlspecialchars($esame['titolo']) ?></h4>
            <p class="text-muted mb-0">
                Classe: <strong><?= htmlspecialchars($esame['classe_nome']) ?></strong>
                <?php if ($esame['materia_nome']): ?>
                    | Materia: <strong><?= htmlspecialchars($esame['materia_nome']) ?></strong>
                <?php endif; ?>
                | ⏱ <?= (int)$esame['durata_minuti'] ?> min
                | ❓ <?= count($domande) ?> domande
                <?php if ($esame['attivo'] && $esame['codice_accesso']): ?>
                    | 🔑 Codice: <strong><?= htmlspecialchars($esame['codice_accesso']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- STATISTICHE -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm stat-card text-center p-3 bg-primary text-white">
                <div class="fs-2 fw-bold"><?= count($sessioni) ?></div>
                <small>Candidati totali</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card text-center p-3 bg-success text-white">
                <div class="fs-2 fw-bold"><?= count($completati) ?></div>
                <small>Esami completati</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card text-center p-3 bg-info text-white">
                <div class="fs-2 fw-bold"><?= number_format($media_punteggio, 1) ?>%</div>
                <small>Media punteggio</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card text-center p-3 bg-warning">
                <div class="fs-2 fw-bold"><?= $promossi ?> / <?= count($completati) ?></div>
                <small>Sufficienti (≥60%)</small>
            </div>
        </div>
    </div>

    <!-- TABELLA CANDIDATI -->
    <div class="card shadow-sm">
        <div class="card-header fw-bold">👥 Elenco Candidati</div>
        <div class="card-body p-0">
            <?php if (empty($sessioni)): ?>
                <p class="p-4 text-muted">Nessun candidato ha ancora acceduto all'esame.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Candidato</th>
                            <th>Registro</th>
                            <th>Iniziato</th>
                            <th>Terminato</th>
                            <th>Punteggio</th>
                            <th>Risposte</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sessioni as $i => $sess):
                        $p    = $sess['completato'] ? (float)$sess['punteggio'] : null;
                        $pcls = is_null($p) ? 'secondary' : ($p >= 60 ? 'success' : ($p >= 40 ? 'warning' : 'danger'));

                        // Conta corrette
                        $corr = 0;
                        foreach ($domande as $dom) {
                            $rid = isset($sess['risposte'][(int)$dom['id']]) ? (int)$sess['risposte'][(int)$dom['id']] : null;
                            foreach ($dom['risposte'] as $r) {
                                if ((int)$r['id'] === $rid && (int)$r['corretta'] === 1) {
                                    $corr++;
                                    break;
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($sess['cognome_candidato'] . ' ' . $sess['nome_candidato']) ?></strong></td>
                            <td>
                                <?php if ($sess['ID_Studente']): ?>
                                    <span class="abbinato">✓ Abbinato</span>
                                <?php else: ?>
                                    <span class="text-muted">— esterno</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('d/m H:i', strtotime($sess['iniziato_il'])) ?></small></td>
                            <td>
                                <?php if ($sess['completato'] && $sess['terminato_il']): ?>
                                    <small><?= date('d/m H:i', strtotime($sess['terminato_il'])) ?></small>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">In corso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sess['completato']): ?>
                                    <span class="punteggio-badge bg-<?= $pcls ?> text-<?= $pcls === 'warning' ? 'dark' : 'white' ?>">
                                        <?= number_format($p, 1) ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= $corr ?>/<?= count($domande) ?></small></td>
                            <td>
                                <button class="btn btn-outline-info btn-sm"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#det-<?= $sess['id'] ?>">
                                    🔍
                                </button>
                            </td>
                        </tr>

                        <!-- DETTAGLIO RISPOSTE -->
                        <tr>
                            <td colspan="8" class="p-0 border-0">
                                <div class="collapse" id="det-<?= $sess['id'] ?>">
                                    <div class="p-3 bg-light">
                                        <h6 class="fw-bold mb-2">
                                            Risposte di <?= htmlspecialchars($sess['nome_candidato'] . ' ' . $sess['cognome_candidato']) ?>
                                        </h6>
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th style="width:30px">#</th>
                                                    <th>Domanda</th>
                                                    <th>Risposta data</th>
                                                    <th>Corretta</th>
                                                    <th style="width:90px">Esito</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($domande as $dom):
                                                $rid    = isset($sess['risposte'][(int)$dom['id']]) ? (int)$sess['risposte'][(int)$dom['id']] : null;
                                                $r_data = null;
                                                $r_corr = null;
                                                foreach ($dom['risposte'] as $r) {
                                                    if ((int)$r['id'] === $rid) $r_data = $r;
                                                    if ((int)$r['corretta'] === 1) $r_corr = $r;
                                                }
                                                $esito_ok = $r_data && (int)$r_data['corretta'] === 1;
                                            ?>
                                            <tr>
                                                <td><?= (int)$dom['ordine'] ?></td>
                                                <td><?= htmlspecialchars(mb_substr($dom['testo'], 0, 60)) ?><?= mb_strlen($dom['testo']) > 60 ? '…' : '' ?></td>
                                                <td>
                                                    <?php if ($r_data): ?>
                                                        <?= htmlspecialchars($r_data['lettera'] . '. ' . $r_data['testo']) ?>
                                                    <?php else: ?>
                                                        <em class="text-muted">Nessuna risposta</em>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($r_corr): ?>
                                                        <?= htmlspecialchars($r_corr['lettera'] . '. ' . $r_corr['testo']) ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$r_data): ?>
                                                        <span class="text-muted">–</span>
                                                    <?php elseif ($esito_ok): ?>
                                                        <span class="risposta-corretta-sm fw-bold">✅ Corretta</span>
                                                    <?php else: ?>
                                                        <span class="risposta-errata-sm fw-bold">❌ Errata</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
