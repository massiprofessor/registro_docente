<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db_connection.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['User_id'];

// Verifica che l'utente sia root
$stmtRoot = $conn->prepare("SELECT root, Username FROM docente WHERE ID_Docente = :id");
$stmtRoot->execute([':id' => $user_id]);
$docente = $stmtRoot->fetch(PDO::FETCH_ASSOC);
if ($docente['root'] !== 'SI') {
    header("Location: access_denied.php");
    exit;
}

$msg_success = '';
$msg_error   = '';

// ── AZIONI POST ──────────────────────────────────────────────────────────────


// Crea nuovo esame
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titolo'], $_POST['classe_id'], $_POST['num_domande'])) {
    try {
        $titolo      = trim($_POST['titolo']);
        $classe_id   = (int)$_POST['classe_id'];
        $materia_id  = !empty($_POST['materia_id']) ? (int)$_POST['materia_id'] : null;
        $num_domande = (int)$_POST['num_domande'];
        $durata      = (int)$_POST['durata_minuti'];
        $stmt = $conn->prepare("INSERT INTO esami (titolo, ID_Classe, ID_Materia, num_domande, durata_minuti, creato_da) VALUES (:t,:c,:m,:n,:d,:u)");
        $stmt->execute([':t' => $titolo, ':c' => $classe_id, ':m' => $materia_id, ':n' => $num_domande, ':d' => $durata, ':u' => $user_id]);
        $esame_id = $conn->lastInsertId();
        header("Location: esami_crea.php?esame_id=$esame_id&nuovo=1");
        exit;
    } catch (Exception $e) {
        $msg_error = "Errore creazione esame: " . $e->getMessage();
    }
}

// Elimina esame
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_esame'])) {
    $eid = (int)$_POST['esame_id'];
    $conn->prepare("DELETE FROM esami WHERE id = :id")->execute([':id' => $eid]);
    $msg_success = "Esame eliminato.";
}

// Attiva / Disattiva esame
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_attivo'])) {
    $eid   = (int)$_POST['esame_id'];
    $attivo = (int)$_POST['attivo_corrente'];

    if ($attivo === 0) {
        // Attiva: genera codice 6 char alfanumerico + scadenza 24h
        $codice   = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        $scadenza = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $conn->prepare("UPDATE esami SET attivo=1, codice_accesso=:c, scadenza_codice=:s WHERE id=:id")
             ->execute([':c' => $codice, ':s' => $scadenza, ':id' => $eid]);
        $msg_success = "Esame attivato! Codice: <strong>$codice</strong> (valido 24h)";
    } else {
        $conn->prepare("UPDATE esami SET attivo=0, codice_accesso=NULL, scadenza_codice=NULL WHERE id=:id")
             ->execute([':id' => $eid]);
        $msg_success = "Esame disattivato.";
    }
}

// Inserisci voti in tabella voti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inserisci_voti'])) {
    $eid        = (int)$_POST['esame_id'];
    $materia_id = (int)$_POST['materia_voto_id'];
    $data_voto  = date('Y-m-d');

    // Carica sessioni completate (senza JOIN cross-collation)
    $stmtSess2 = $conn->prepare("SELECT * FROM esami_sessioni WHERE esame_id = :eid AND completato = 1");
    $stmtSess2->execute([':eid' => $eid]);
    $rows = $stmtSess2->fetchAll(PDO::FETCH_ASSOC);

    $inseriti = 0;
    foreach ($rows as $r) {
        // Cerca studente per nome/cognome nella classe (query separata, no cross-collation)
        $stmtStu2 = $conn->prepare("SELECT ID_Studente FROM studenti WHERE Nome COLLATE utf8mb4_uca1400_ai_ci = :n AND Cognome COLLATE utf8mb4_uca1400_ai_ci = :c LIMIT 1");
        $stmtStu2->execute([':n' => $r['nome_candidato'], ':c' => $r['cognome_candidato']]);
        $stu2 = $stmtStu2->fetchColumn();
        if (!$stu2) continue;
        // Controlla se voto già presente — colonna corretta: ID_Voto
        $chk = $conn->prepare("SELECT ID_Voto FROM voti WHERE ID_Studente=:s AND ID_Materia=:m AND Data=:d");
        $chk->execute([':s' => $stu2, ':m' => $materia_id, ':d' => $data_voto]);
        if ($chk->fetch()) continue;
        $conn->prepare("INSERT INTO voti (ID_Studente, ID_Materia, Voto, Data) VALUES (:s,:m,:v,:d)")
             ->execute([':s' => $stu2, ':m' => $materia_id, ':v' => round($r['punteggio']), ':d' => $data_voto]);
        $inseriti++;
    }
    $msg_success = "Inseriti $inseriti voti nel registro.";
}

// ── DATI PER LA VISTA ────────────────────────────────────────────────────────

// Classi
$classi  = $conn->query("SELECT ID_Classe, Nome FROM classi ORDER BY Nome")->fetchAll(PDO::FETCH_ASSOC);
// Materie
$materie = $conn->query("SELECT ID_Materia, Materia FROM materie ORDER BY Materia")->fetchAll(PDO::FETCH_ASSOC);

// Lista esami con conteggi — query semplificata per compatibilità
try {
    $stmtEsami = $conn->query("
        SELECT e.*, c.Nome AS classe_nome, m.Materia AS materia_nome
        FROM esami e
        JOIN classi c ON e.ID_Classe = c.ID_Classe
        LEFT JOIN materie m ON e.ID_Materia = m.ID_Materia
        ORDER BY e.data_creazione DESC
    ");
    $esami_raw = $stmtEsami->fetchAll(PDO::FETCH_ASSOC);
    $esami = [];
    foreach ($esami_raw as $er) {
        $s1 = $conn->prepare("SELECT COUNT(*) FROM esami_domande WHERE esame_id=:id");
        $s1->execute([':id' => $er['id']]);
        $er['tot_domande'] = (int)$s1->fetchColumn();
        $s2 = $conn->prepare("SELECT COUNT(*) FROM esami_sessioni WHERE esame_id=:id AND completato=1");
        $s2->execute([':id' => $er['id']]);
        $er['tot_completati'] = (int)$s2->fetchColumn();
        $esami[] = $er;
    }
} catch (Exception $e) {
    $msg_error = "Errore caricamento esami: " . $e->getMessage();
    $esami = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Esami</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge-codice { font-size:1.1rem; letter-spacing:3px; }
        .card-esame { border-left: 4px solid #0d6efd; }
        .card-esame.attivo { border-left-color: #198754; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand">🎓 Gestione Esami — Admin</span>
        <div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <?php if ($msg_success): ?>
        <div class="alert alert-success"><?= $msg_success ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><?= $msg_error ?></div>
    <?php endif; ?>

    <!-- ── CREA NUOVO ESAME ─────────────────────────────────── -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">➕ Crea Nuovo Esame</div>
        <div class="card-body">
            <form method="POST" action="esami.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titolo Esame</label>
                        <input type="text" name="titolo" class="form-control" required placeholder="es. Verifica Reti Capitolo 3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($classi as $c): ?>
                                <option value="<?= $c['ID_Classe'] ?>"><?= htmlspecialchars($c['Nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Materia (opzionale)</label>
                        <select name="materia_id" class="form-select">
                            <option value="">-- Nessuna --</option>
                            <?php foreach ($materie as $m): ?>
                                <option value="<?= $m['ID_Materia'] ?>"><?= htmlspecialchars($m['Materia']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Numero Domande</label>
                        <input type="number" name="num_domande" class="form-control" min="1" max="100" required value="10">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Durata (minuti)</label>
                        <input type="number" name="durata_minuti" class="form-control" min="1" max="300" required value="30">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Crea Esame e Inserisci Domande →</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── LISTA ESAMI ─────────────────────────────────────── -->
    <h4 class="mb-3">📋 Esami Esistenti</h4>

    <?php if (empty($esami)): ?>
        <div class="alert alert-info">Nessun esame creato.</div>
    <?php endif; ?>

    <?php foreach ($esami as $e): ?>
        <?php
            $scaduto = $e['scadenza_codice'] && strtotime($e['scadenza_codice']) < time();
            $attivo_reale = $e['attivo'] && !$scaduto;
        ?>
        <div class="card shadow-sm mb-3 card-esame <?= $attivo_reale ? 'attivo' : '' ?>">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <h5 class="mb-1"><?= htmlspecialchars($e['titolo']) ?></h5>
                        <small class="text-muted">
                            Classe: <strong><?= htmlspecialchars($e['classe_nome']) ?></strong>
                            <?= $e['materia_nome'] ? ' | Materia: <strong>'.htmlspecialchars($e['materia_nome']).'</strong>' : '' ?>
                            | ⏱ <?= $e['durata_minuti'] ?> min
                            | ❓ <?= $e['tot_domande'] ?>/<?= $e['num_domande'] ?> domande
                            | 📊 <?= $e['tot_completati'] ?> completati
                        </small>
                    </div>
                    <div class="col-md-3 text-center">
                        <?php if ($attivo_reale): ?>
                            <span class="badge bg-success badge-codice"><?= $e['codice_accesso'] ?></span><br>
                            <small class="text-muted">Scade: <?= date('d/m H:i', strtotime($e['scadenza_codice'])) ?></small>
                        <?php elseif ($e['attivo'] && $scaduto): ?>
                            <span class="badge bg-warning text-dark">Codice Scaduto</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Non attivo</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end d-flex gap-2 flex-wrap justify-content-end">
                        <?php if ($e['tot_domande'] < $e['num_domande']): ?>
                            <a href="esami_crea.php?esame_id=<?= $e['id'] ?>" class="btn btn-warning btn-sm">✏️ Completa</a>
                        <?php else: ?>
                            <a href="esami_crea.php?esame_id=<?= $e['id'] ?>" class="btn btn-outline-secondary btn-sm">✏️ Modifica</a>
                        <?php endif; ?>
                        <a href="esami_risultati.php?esame_id=<?= $e['id'] ?>" class="btn btn-info btn-sm text-white">📊 Risultati</a>

                        <!-- Attiva/Disattiva -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="esame_id" value="<?= $e['id'] ?>">
                            <input type="hidden" name="attivo_corrente" value="<?= $attivo_reale ? 1 : 0 ?>">
                            <button name="toggle_attivo" class="btn btn-sm <?= $attivo_reale ? 'btn-danger' : 'btn-success' ?>">
                                <?= $attivo_reale ? '🔴 Disattiva' : '🟢 Attiva' ?>
                            </button>
                        </form>

                        <!-- Inserisci voti -->
                        <?php if ($e['tot_completati'] > 0): ?>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVoti<?= $e['id'] ?>">
                            📝 → Voti
                        </button>
                        <?php endif; ?>

                        <!-- Elimina -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Eliminare questo esame e tutti i dati correlati?')">
                            <input type="hidden" name="esame_id" value="<?= $e['id'] ?>">
                            <button name="elimina_esame" class="btn btn-outline-danger btn-sm">🗑️</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal inserimento voti -->
        <?php if ($e['tot_completati'] > 0): ?>
        <div class="modal fade" id="modalVoti<?= $e['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Inserisci voti nel registro</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="esame_id" value="<?= $e['id'] ?>">
                            <p>I punteggi dell'esame <strong><?= htmlspecialchars($e['titolo']) ?></strong> verranno inseriti come voti nel registro per gli studenti della classe abbinati per nome e cognome.</p>
                            <label class="form-label">Seleziona Materia per il voto</label>
                            <select name="materia_voto_id" class="form-select" required>
                                <option value="">-- Seleziona materia --</option>
                                <?php foreach ($materie as $m): ?>
                                    <option value="<?= $m['ID_Materia'] ?>" <?= $e['ID_Materia'] == $m['ID_Materia'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['Materia']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Solo i candidati abbinati a uno studente della classe riceveranno il voto.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                            <button type="submit" name="inserisci_voti" class="btn btn-primary">Inserisci Voti</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
