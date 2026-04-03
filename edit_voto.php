<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';

if (!isset($_GET['voto'], $_GET['classe'])) {
    header("Location: gestione_voti.php");
    exit;
}

$voto_id  = (int)$_GET['voto'];
$classe_id = (int)$_GET['classe'];
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voto = (int)$_POST['voto'];
    $data = $_POST['data'];

    if ($voto > 0 && !empty($data)) {
        $stmt = $conn->prepare("UPDATE voti SET Voto = :v, Data = :d WHERE ID_Voto = :id");
        if ($stmt->execute([':v' => $voto, ':d' => $data, ':id' => $voto_id])) {
            header("Location: gestione_voti.php?classe=$classe_id");
            exit;
        } else {
            $error = "Errore durante la modifica del voto.";
        }
    } else {
        $error = "Il voto e la data sono obbligatori.";
    }
}

$stmt = $conn->prepare("SELECT Voto, Data FROM voti WHERE ID_Voto = :id");
$stmt->execute([':id' => $voto_id]);
$voto_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voto_data) {
    die("Errore: voto non trovato.");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Voto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Modifica Voto</h1>
        <hr>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="voto" class="form-label">Voto</label>
                <input type="number" name="voto" id="voto" class="form-control" value="<?= htmlspecialchars($voto_data['Voto']) ?>" min="0" max="100" required>
            </div>
            <div class="mb-3">
                <label for="data" class="form-label">Data</label>
                <input type="date" name="data" id="data" class="form-control" value="<?= htmlspecialchars($voto_data['Data']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="gestione_voti.php?classe=<?= $classe_id ?>" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</body>
</html>
