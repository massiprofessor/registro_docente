<?php
session_start();

// Connessione al database
require_once 'db_connection.php';

// Recupera l'ID del docente loggato (simulazione di login)
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

// Ottieni ID docente dalla sessione
$docente_id = $_SESSION['User_id'];

// Recupera i parametri GET
if (!isset($_GET['materia_id']) || !isset($_GET['classe_id'])) {
    die("Errore: Parametri mancanti.");
}
$materia_id = $_GET['materia_id'];
$classe_id = $_GET['classe_id'];
$id = $_GET['scheduling_id'];

// Funzioni utili
function getLezioniByScheduling($conn, $materia_id, $classe_id, $id) {
    $sql = "SELECT l.id, l.numero_lezione, l.completato, l.commento, l.scheduling_id
FROM lezioni l
WHERE l.materia_id = ? AND l.classe_id = ? AND l.scheduling_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id, $classe_id,$id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getArgomentiByMateria($conn, $materia_id) {
    $sql = "SELECT * FROM argomenti WHERE materia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getArgomentiByLezioni($conn, $lezione_id, $id) {
    $sql = "SELECT la.argomento_id
            FROM lezioni_argomenti la
            WHERE la.lezione_id = ? AND la.scheduling_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$lezione_id, $id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_lesson']) && isset($_POST['lezione_id']) && isset($_POST['argomento_ids']) && isset($_POST['commento'])) {
        $lezione_id = $_POST['lezione_id'];
        $argomento_ids = $_POST['argomento_ids'];
        $commento = $_POST['commento'];

        // Aggiorna il commento nella tabella lezioni
        $sql = "UPDATE lezioni SET commento = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$commento, $lezione_id]);

        // Aggiorna gli argomenti associati nella tabella lezioni_argomenti
        $sql = "DELETE FROM lezioni_argomenti WHERE lezione_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lezione_id]);

        foreach ($argomento_ids as $argomento_id) {
            $sql = "INSERT INTO lezioni_argomenti (lezione_id, argomento_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$lezione_id, $argomento_id]);
        }
    }
}

$lezioni = getLezioniByScheduling($conn, $materia_id, $classe_id,$id);
$argomenti = getArgomentiByMateria($conn, $materia_id);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Scheduling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestione Scheduling</h1>
    <a href="scheduling.php?materia_id=<?= $materia_id ?>&classe_id=<?= $classe_id ?>&id=<?= $id ?>" class="btn btn-secondary">
        Torna alla Pagina Precedente
    </a>
</div>

    <h2 class="h4">Dettagli Scheduling</h2>
    <form method="POST">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Numero Lezione</th>
                    <th>Argomenti</th>
                    <th>Commento</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($lezioni as $lezione): ?>
    <?php 
        // Recupera argomenti selezionati come array chiave-valore per confronto veloce
        $argomenti_selezionati = array_flip(getArgomentiByLezioni($conn, $lezione['id'], $id));
    ?>
    <form method="POST" id="form-<?= $lezione['id'] ?>">
        <tr>
            <td><?= $lezione['numero_lezione'] ?></td>
            <td>
                <select name="argomento_ids[]" class="form-select" multiple>
                    <?php foreach ($argomenti as $argomento): ?>
                        <?php $isSelected = isset($argomenti_selezionati[$argomento['id']]); ?>
                        <option value="<?= $argomento['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($argomento['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="commento" class="form-control" value="<?= htmlspecialchars($lezione['commento']) ?>">
            </td>
            <td>
                <input type="hidden" name="lezione_id" value="<?= $lezione['id'] ?>">
                <button type="submit" name="update_lesson" class="btn btn-primary">Salva</button>
            </td>
        </tr>
    </form>
<?php endforeach; ?>

            </tbody>
        </table>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
