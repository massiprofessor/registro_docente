<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';

$docente_id = $_SESSION['User_id'];

// Classi del docente — PDO puro
$stmt_classi = $conn->prepare("
    SELECT c.ID_Classe, c.Nome 
    FROM classi c 
    INNER JOIN classi_docenti cd ON c.ID_Classe = cd.ID_C
    WHERE cd.ID_D = :did");
$stmt_classi->execute([':did' => $docente_id]);
$classi = $stmt_classi->fetchAll(PDO::FETCH_ASSOC);

$alunni  = [];
$materie = [];
if (isset($_GET['classe'])) {
    $classe_id = (int)$_GET['classe'];

    $stmt_alunni = $conn->prepare("SELECT ID_Studente, Nome, Cognome FROM studenti WHERE ID_Classe = :cid");
    $stmt_alunni->execute([':cid' => $classe_id]);
    $alunni = $stmt_alunni->fetchAll(PDO::FETCH_ASSOC);

    $stmt_materie = $conn->prepare("
        SELECT m.ID_Materia, m.Materia
        FROM classi_materie cm
        INNER JOIN materie m ON cm.ID_M = m.ID_Materia
        WHERE cm.ID_C = :cid");
    $stmt_materie->execute([':cid' => $classe_id]);
    $materie = $stmt_materie->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Voti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-primary">Gestione Voti</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">Torna alla Dashboard</a>
        </div>

        <form method="GET" class="mb-4">
            <label for="classe" class="form-label">Seleziona una classe:</label>
            <select name="classe" id="classe" class="form-select" onchange="this.form.submit()">
                <option value="" selected disabled>-- Seleziona --</option>
                <?php foreach ($classi as $classe): ?>
                    <option value="<?= $classe['ID_Classe'] ?>" <?= isset($classe_id) && $classe_id == $classe['ID_Classe'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($classe['Nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!empty($alunni) && !empty($materie)): ?>
            <table class="table table-bordered table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>Studente</th>
                        <?php foreach ($materie as $materia): ?>
                            <th><?= htmlspecialchars($materia['Materia']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunni as $alunno): ?>
                        <tr>
                            <td>
                                <a href="scheda_alunno.php?studente=<?= $alunno['ID_Studente'] ?>&classe=<?= $classe_id ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($alunno['Nome'] . ' ' . $alunno['Cognome']) ?>
                                </a>
                            </td>
                            <?php foreach ($materie as $materia): ?>
                                <?php
                                $stmt_voto = $conn->prepare("SELECT ID_Voto, Voto FROM voti WHERE ID_Studente = :sid AND ID_Materia = :mid");
                                $stmt_voto->execute([':sid' => $alunno['ID_Studente'], ':mid' => $materia['ID_Materia']]);
                                $voto = $stmt_voto->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <td>
                                    <?php if ($voto): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($voto['Voto']) ?></span>
                                        <a href="edit_voto.php?voto=<?= $voto['ID_Voto'] ?>&classe=<?= $classe_id ?>" class="btn btn-sm btn-primary mt-1">Edita</a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                        <a href="add_voto.php?studente=<?= $alunno['ID_Studente'] ?>&classe=<?= $classe_id ?>&materia=<?= $materia['ID_Materia'] ?>" class="btn btn-sm btn-success mt-1">Aggiungi</a>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (isset($classe_id)): ?>
            <div class="alert alert-warning">Nessun alunno o materia trovata per questa classe.</div>
        <?php endif; ?>
    </div>
</body>
</html>
