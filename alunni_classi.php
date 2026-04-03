<?php
session_start();

// Controlla se l'utente è loggato
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

    $queryClassi = $is_root 
        ? $conn->query("SELECT ID_Classe, Nome FROM classi") 
        : $conn->prepare("SELECT c.ID_Classe, c.Nome FROM classi c
                          JOIN classi_docenti cd ON c.ID_Classe = cd.ID_C
                          WHERE cd.ID_D = :user_id");

    if (!$is_root) {
        $queryClassi->execute([':user_id' => $user_id]);
    }

    $classi = $queryClassi->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Elimina uno studente
        if (isset($_POST['delete_single'])) {
            $student_id = (int)$_POST['student_id'];
            $stmt = $conn->prepare("DELETE FROM studenti WHERE ID_Studente = :id_studente");
            $stmt->execute([':id_studente' => $student_id]);
            $success_message = "Studente eliminato con successo!";
        }

        // Elimina tutti gli studenti di una classe
        if (isset($_POST['delete_all'])) {
            $id_classe = (int)$_POST['id_classe'];
            $stmt = $conn->prepare("DELETE FROM studenti WHERE ID_Classe = :id_classe");
            $stmt->execute([':id_classe' => $id_classe]);
            $success_message = "Tutti gli studenti della classe sono stati eliminati!";
        }

        // Aggiungi manualmente uno studente
        if (isset($_POST['add_student'])) {
            $nome = trim($_POST['nome']);
            $cognome = trim($_POST['cognome']);
            $id_classe = (int)$_POST['id_classe'];

            if (!empty($nome) && !empty($cognome)) {
                $stmt = $conn->prepare("INSERT INTO studenti (ID_Classe, Nome, Cognome) VALUES (:id_classe, :nome, :cognome)");
                $stmt->execute([':id_classe' => $id_classe, ':nome' => $nome, ':cognome' => $cognome]);
                $success_message = "Studente aggiunto con successo!";
            } else {
                $error_message = "Nome e Cognome sono obbligatori.";
            }
        }

        // Importa studenti da file
        if (isset($_FILES['file_import']) && isset($_POST['id_classe'])) {
            $id_classe = (int)$_POST['id_classe'];
            $file = $_FILES['file_import']['tmp_name'];
            $ext = pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION);

            if ($ext === 'csv') {
                $data = readCSV($file);
            } elseif ($ext === 'xlsx') {
                $data = readXLSX($file);
            } else {
                $error_message = "Formato file non supportato. Caricare un file .csv o .xlsx.";
            }

            if (!empty($data)) {
                $stmt = $conn->prepare("INSERT INTO studenti (ID_Classe, Nome, Cognome) VALUES (:id_classe, :nome, :cognome)");
                foreach ($data as $index => $row) {
                    if ($index === 0) continue;
                    $nome = trim($row[0]);
                    $cognome = trim($row[1]);
                    if (!empty($nome) && !empty($cognome)) {
                        $stmt->execute([':id_classe' => $id_classe, ':nome' => $nome, ':cognome' => $cognome]);
                    }
                }
                $success_message = "Studenti importati con successo!";
            }
        }
    }

    $students = [];
    if (isset($_GET['id_classe'])) {
        $id_classe = (int)$_GET['id_classe'];
        $queryStudents = $conn->prepare("SELECT ID_Studente, Nome, Cognome FROM studenti WHERE ID_Classe = :id_classe");
        $queryStudents->execute([':id_classe' => $id_classe]);
        $students = $queryStudents->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Errore nella connessione al database: " . $e->getMessage());
}

function readCSV($file) {
    $data = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

function readXLSX($file) {
    $zip = new ZipArchive;
    $data = [];

    if ($zip->open($file) === TRUE) {
        $sharedStrings = [];
        $workbookXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($workbookXML) {
            $sharedStringsXML = new SimpleXMLElement($workbookXML);
            foreach ($sharedStringsXML->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }

        $sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sheet = new SimpleXMLElement($sheetXML);

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $value = (string)$cell->v;
                $type = (string)$cell['t'];
                if ($type === 's' && isset($sharedStrings[intval($value)])) {
                    $rowData[] = $sharedStrings[intval($value)];
                } else {
                    $rowData[] = $value;
                }
            }
            $data[] = $rowData;
        }
        $zip->close();
    }
    return $data;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Studenti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 1200px;
            margin-top: 30px;
        }
        .button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .form-group label {
            font-weight: bold;
        }
        .success, .error {
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #28a745;
            color: white;
        }
        .error {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestione Studenti</h1>
            <a href="dashboard.php" class="btn btn-secondary">Torna alla Dashboard</a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="get" action="" class="mb-4">
            <div class="form-group">
                <label for="id_classe">Seleziona Classe:</label>
                <select name="id_classe" id="id_classe" class="form-control" required onchange="this.form.submit()">
                    <option value="">-- Seleziona una classe --</option>
                    <?php foreach ($classi as $classe): ?>
                        <option value="<?= $classe['ID_Classe'] ?>" <?= isset($id_classe) && $id_classe == $classe['ID_Classe'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($classe['Nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($students)): ?>
            <h2>Studenti nella Classe Selezionata</h2>
            <form method="post" class="mb-3">
                <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
                <button type="submit" name="delete_all" class="btn btn-danger">Elimina Tutti gli Studenti</button>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID Studente</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $student['ID_Studente'] ?></td>
                            <td><?= htmlspecialchars($student['Nome']) ?></td>
                            <td><?= htmlspecialchars($student['Cognome']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="student_id" value="<?= $student['ID_Studente'] ?>">
                                    <button type="submit" name="delete_single" class="btn btn-danger">Elimina</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Aggiungi uno Studente Manualmente</h2>
        <form method="post" class="mb-4">
            <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome:</label>
                <input type="text" name="cognome" id="cognome" class="form-control" required>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary">Aggiungi Studente</button>
        </form>

        <h2>Importa Studenti da File</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
            <div class="form-group">
                <label for="file_import">Carica File (CSV o XLSX):</label>
                <input type="file" name="file_import" accept=".csv, .xlsx" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Importa</button>
        </form>
    </div>
</body>
</html>
