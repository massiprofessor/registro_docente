<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';

$error = '';

if (isset($_GET['studente'], $_GET['classe'], $_GET['materia']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $studente_id = (int)$_GET['studente'];
    $classe_id   = (int)$_GET['classe'];
    $materia_id  = (int)$_GET['materia'];
    $voto        = (int)$_POST['voto'];
    $data        = $_POST['data'];

    $stmt = $conn->prepare("INSERT INTO voti (ID_Studente, ID_Materia, Voto, Data) VALUES (:sid, :mid, :v, :d)");
    if ($stmt->execute([':sid' => $studente_id, ':mid' => $materia_id, ':v' => $voto, ':d' => $data])) {
        header("Location: gestione_voti.php?classe=$classe_id");
        exit;
    } else {
        $error = "Errore durante l'inserimento del voto.";
    }
}

$materia = ['Materia' => ''];
if (isset($_GET['materia'])) {
    $stmt_m = $conn->prepare("SELECT Materia FROM materie WHERE ID_Materia = :id");
    $stmt_m->execute([':id' => (int)$_GET['materia']]);
    $materia = $stmt_m->fetch(PDO::FETCH_ASSOC) ?: $materia;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Voto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Aggiungi Voto</h1>
        <hr>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Materia</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($materia['Materia']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="voto" class="form-label">Voto</label>
                <input type="number" name="voto" id="voto" class="form-control" min="0" max="100" required>
            </div>
            <div class="mb-3">
                <label for="data" class="form-label">Data</label>
                <input type="date" name="data" id="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="btn btn-success">Aggiungi</button>
            <a href="gestione_voti.php?classe=<?= htmlspecialchars($_GET['classe'] ?? '') ?>" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</body>
</html>
