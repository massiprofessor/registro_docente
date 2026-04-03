<?php
session_start();
session_regenerate_id();

if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';
try {

    $user_id = $_SESSION['User_id'];
    $queryUser = $conn->prepare("SELECT root FROM docente WHERE ID_Docente = :user_id");
    $queryUser->execute([':user_id' => $user_id]);
    $user = $queryUser->fetch(PDO::FETCH_ASSOC);

    $is_root = ($user['root'] === "SI");

$sql_classi = $is_root ? "SELECT ID_Classe, Nome FROM classi" : "
    SELECT c.ID_Classe, c.Nome 
    FROM classi c 
    JOIN classi_docenti dc ON c.ID_Classe = dc.ID_C 
    WHERE dc.ID_D = :user_id";
$stmt = $is_root ? $conn->query($sql_classi) : $conn->prepare($sql_classi);
if (!$is_root) $stmt->execute([':user_id' => $user_id]);
    $stmt = $is_root ? $conn->query($sql_classi) : $conn->prepare($sql_classi);
    if (!$is_root) $stmt->execute([':user_id' => $user_id]);
    $classi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $id_classe = $_POST['id_classe'] ?? ($_GET['id_classe'] ?? null);
    $data = $_POST['data'] ?? ($_GET['data'] ?? date('Y-m-d'));
    $studenti = [];
    $presenze_giorno_assoc = [];
    $ore_cumulative = [];

    if ($id_classe) {
        $queryStudenti = $conn->prepare("SELECT * FROM studenti WHERE ID_Classe = :id_classe");
        $queryStudenti->execute([':id_classe' => $id_classe]);
        $studenti = $queryStudenti->fetchAll(PDO::FETCH_ASSOC);

        $queryImpostazioni = $conn->prepare("SELECT Ore_Mattina, Ore_Pomeriggio FROM impostazioni_classe WHERE ID_Classe = :id_classe");
        $queryImpostazioni->execute([':id_classe' => $id_classe]);
        $impostazioni = $queryImpostazioni->fetch(PDO::FETCH_ASSOC);
        $ore_mattina = $impostazioni['Ore_Mattina'] ?? 0;
        $ore_pomeriggio = $impostazioni['Ore_Pomeriggio'] ?? 0;

        $queryPresenze = $conn->prepare("
            SELECT ID_Studente, Presenza_Mattina, Presenza_Pomeriggio 
            FROM presenze 
            WHERE ID_Classe = :id_classe AND Data = :data
        ");
        $queryPresenze->execute([':id_classe' => $id_classe, ':data' => $data]);
        $presenze_giorno = $queryPresenze->fetchAll(PDO::FETCH_ASSOC);

        foreach ($presenze_giorno as $presenza) {
            $presenze_giorno_assoc[$presenza['ID_Studente']] = [
                'Mattina' => $presenza['Presenza_Mattina'],
                'Pomeriggio' => $presenza['Presenza_Pomeriggio']
            ];
        }

        $queryOreCumulative = $conn->prepare("
            SELECT ID_Studente, 
                   SUM(COALESCE(Presenza_Mattina, 0) * :ore_mattina +
                       COALESCE(Presenza_Pomeriggio, 0) * :ore_pomeriggio) AS Ore_Frequentate
            FROM presenze
            WHERE ID_Classe = :id_classe AND Data <= :data
            GROUP BY ID_Studente
        ");
        $queryOreCumulative->execute([
            ':id_classe' => $id_classe,
            ':data' => $data,
            ':ore_mattina' => $ore_mattina,
            ':ore_pomeriggio' => $ore_pomeriggio
        ]);
        $ore_cumulative = $queryOreCumulative->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_classe'], $_POST['data'], $_POST['studenti_ids'])) {
        // Itera su TUTTI gli studenti inviati (hidden input), non solo i checkbox checked
        // I checkbox non vengono inviati se deselezionati, quindi usiamo studenti_ids come lista completa
        $studenti_ids = explode(',', $_POST['studenti_ids']);
        $presenze_post = $_POST['presenze'] ?? [];

        foreach ($studenti_ids as $id_studente) {
            $id_studente = (int)$id_studente;
            if ($id_studente <= 0) continue;

            // Se lo studente non è in $_POST['presenze'] entrambi i checkbox sono deselezionati
            $am = isset($presenze_post[$id_studente]['am']) ? 1 : 0;
            $pm = isset($presenze_post[$id_studente]['pm']) ? 1 : 0;

            $stmt_check = $conn->prepare("SELECT ID_Presenza FROM presenze WHERE ID_Classe = :id_classe AND ID_Studente = :id_studente AND Data = :data");
            $stmt_check->execute([':id_classe' => $_POST['id_classe'], ':id_studente' => $id_studente, ':data' => $_POST['data']]);
            $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $conn->prepare("UPDATE presenze SET Presenza_Mattina = :am, Presenza_Pomeriggio = :pm WHERE ID_Classe = :id_classe AND ID_Studente = :id_studente AND Data = :data")
                     ->execute([':id_classe' => $_POST['id_classe'], ':id_studente' => $id_studente, ':data' => $_POST['data'], ':am' => $am, ':pm' => $pm]);
            } else {
                $conn->prepare("INSERT INTO presenze (ID_Classe, ID_Studente, Data, Presenza_Mattina, Presenza_Pomeriggio) VALUES (:id_classe, :id_studente, :data, :am, :pm)")
                     ->execute([':id_classe' => $_POST['id_classe'], ':id_studente' => $id_studente, ':data' => $_POST['data'], ':am' => $am, ':pm' => $pm]);
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?id_classe=" . $_POST['id_classe'] . "&data=" . $_POST['data']);
        exit;
    }

} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Presenze</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center mb-0">Gestione Presenze</h1>
            <a href="dashboard.php" class="btn btn-secondary">Torna alla Dashboard</a>
        </div>
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="id_classe" class="form-label">Seleziona Classe</label>
                    <select name="id_classe" id="id_classe" class="form-select" required>
                        <option value="">-- Seleziona Classe --</option>
                        <?php foreach ($classi as $classe): ?>
                            <option value="<?= $classe['ID_Classe'] ?>" <?= $classe['ID_Classe'] == $id_classe ? 'selected' : '' ?>>
                                <?= htmlspecialchars($classe['Nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="data" class="form-label">Seleziona Data</label>
                    <input type="date" name="data" id="data" class="form-control" value="<?= $data ?>" required>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary px-4">Visualizza Studenti</button>
            </div>
        </form>

        <?php if ($id_classe): ?>
            <form method="POST">
                <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
                <input type="hidden" name="data" value="<?= $data ?>">
                <input type="hidden" name="studenti_ids" value="<?= implode(',', array_column($studenti, 'ID_Studente')) ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Cognome</th>
                                <th>Mattina</th>
                                <th>Pomeriggio</th>
                                <th>Ore Frequentate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studenti as $studente): ?>
                                <tr>
                                    <td><?= htmlspecialchars($studente['Nome']) ?></td>
                                    <td><?= htmlspecialchars($studente['Cognome']) ?></td>
                                    <td class="text-center">
                                        <input type="checkbox" name="presenze[<?= $studente['ID_Studente'] ?>][am]" <?= $presenze_giorno_assoc[$studente['ID_Studente']]['Mattina'] ?? 0 ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="presenze[<?= $studente['ID_Studente'] ?>][pm]" <?= $presenze_giorno_assoc[$studente['ID_Studente']]['Pomeriggio'] ?? 0 ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center">
                                        <?= $ore_cumulative[$studente['ID_Studente']] ?? 0 ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-success px-4">Registra Presenze</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
