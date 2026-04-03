<?php
session_start();

require_once 'db_connection.php';

$msg_error = '';

// ── INIZIO SESSIONE ESAME ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inizia_esame'])) {
    $codice  = strtoupper(trim($_POST['codice']));
    $nome    = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);

    // Cerca esame attivo con codice valido e non scaduto
    $stmt = $conn->prepare("
        SELECT e.*, c.Nome AS classe_nome
        FROM esami e
        JOIN classi c ON e.ID_Classe = c.ID_Classe
        WHERE e.codice_accesso = :codice
          AND e.attivo = 1
          AND e.scadenza_codice > NOW()
    ");
    $stmt->execute([':codice' => $codice]);
    $esame = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$esame) {
        $msg_error = "Codice non valido o scaduto. Chiedi il codice al docente.";
    } elseif (empty($nome) || empty($cognome)) {
        $msg_error = "Inserisci nome e cognome completi.";
    } else {
        // Verifica se questo candidato ha già completato l'esame
        $chk = $conn->prepare("SELECT id, completato FROM esami_sessioni WHERE esame_id=:e AND nome_candidato=:n AND cognome_candidato=:c");
        $chk->execute([':e' => $esame['id'], ':n' => $nome, ':c' => $cognome]);
        $sessione_exist = $chk->fetch(PDO::FETCH_ASSOC);

        if ($sessione_exist && $sessione_exist['completato']) {
            $msg_error = "Hai già completato questo esame.";
        } else {
            // Trova studente abbinato (se esiste)
            $stmtStu = $conn->prepare("SELECT ID_Studente FROM studenti WHERE ID_Classe=:cl AND Nome=:n AND Cognome=:c");
            $stmtStu->execute([':cl' => $esame['ID_Classe'], ':n' => $nome, ':c' => $cognome]);
            $studente = $stmtStu->fetch(PDO::FETCH_ASSOC);
            $studente_id = $studente ? $studente['ID_Studente'] : null;

            if ($sessione_exist) {
                // Riprendi sessione esistente
                $sessione_id = $sessione_exist['id'];
            } else {
                // Crea nuova sessione
                $stmtIns = $conn->prepare("INSERT INTO esami_sessioni (esame_id, nome_candidato, cognome_candidato, ID_Studente) VALUES (:e,:n,:c,:s)");
                $stmtIns->execute([':e' => $esame['id'], ':n' => $nome, ':c' => $cognome, ':s' => $studente_id]);
                $sessione_id = $conn->lastInsertId();
            }

            // Salva in sessione PHP
            $_SESSION['esame_sessione_id'] = $sessione_id;
            $_SESSION['esame_id']          = $esame['id'];
            $_SESSION['candidato_nome']    = $nome . ' ' . $cognome;

            header("Location: esami_svolgi.php");
            exit;
        }
    }
}

// Precompila codice se arriva da URL
$codice_url = isset($_GET['codice']) ? strtoupper(trim($_GET['codice'])) : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso Esame</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #0d6efd 100%); min-height: 100vh; display: flex; align-items: center; }
        .card-accesso { border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.25); }
        .brand-title { font-size: 2rem; font-weight: 700; color: #0d6efd; }
        .codice-input { font-size: 1.8rem; text-align: center; letter-spacing: 8px; font-weight: 700; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-accesso p-4">
                <div class="text-center mb-4">
                    <div class="brand-title">🎓 Esame</div>
                    <p class="text-muted">Accademia del Levante</p>
                </div>

                <?php if ($msg_error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($msg_error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Codice Esame</label>
                        <input type="text" name="codice" class="form-control codice-input"
                               maxlength="6" required
                               value="<?= htmlspecialchars($codice_url) ?>"
                               placeholder="XXXXXX"
                               oninput="this.value = this.value.toUpperCase()">
                        <div class="form-text text-center">Inserisci il codice fornito dal docente</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label fw-bold">Nome</label>
                            <input type="text" name="nome" class="form-control" required placeholder="Mario">
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold">Cognome</label>
                            <input type="text" name="cognome" class="form-control" required placeholder="Rossi">
                        </div>
                    </div>
                    <button type="submit" name="inizia_esame" class="btn btn-primary w-100 py-2 fw-bold fs-5">
                        Inizia Esame →
                    </button>
                </form>

                <hr class="my-4">
                <div class="text-center">
                    <small class="text-muted">Sei un docente? <a href="login.html">Accedi al pannello</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
