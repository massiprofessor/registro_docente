<?php
session_start();

// Controlla se l'utente è loggato
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';
try {

    // Recupera i dettagli dell'utente loggato
    $user_id = $_SESSION['User_id'];
    $queryUser = $conn->prepare("SELECT root FROM docente WHERE ID_Docente = :user_id");
    $queryUser->execute([':user_id' => $user_id]);
    $user = $queryUser->fetch(PDO::FETCH_ASSOC);

    // Verifica se l'utente è root
    $is_root = ($user['root'] === "SI");

    // Recupera l'elenco delle materie
    $queryMaterie = $conn->query("SELECT ID_Materia, Materia FROM materie");
    $materie = $queryMaterie->fetchAll(PDO::FETCH_ASSOC);

    // Recupera l'elenco dei docenti
    $queryDocenti = $conn->query("SELECT ID_Docente, Username FROM docente");
    $docenti = $queryDocenti->fetchAll(PDO::FETCH_ASSOC);

    // Gestione eliminazione classe (solo per root)
    if ($is_root && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id_classe = (int)$_GET['id'];
        $conn->prepare("DELETE FROM classi_materie WHERE ID_C = :id_classe")->execute([':id_classe' => $id_classe]);
        $conn->prepare("DELETE FROM classi_docenti WHERE ID_C = :id_classe")->execute([':id_classe' => $id_classe]);
        $conn->prepare("DELETE FROM classi WHERE ID_Classe = :id_classe")->execute([':id_classe' => $id_classe]);
        header("Location: gestione_classi.php");
        exit;
    }

    // Gestione modifica classe (solo per root)
    if ($is_root && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifica_classe']) && isset($_POST['id_classe'])) {
        $id_classe = (int)$_POST['id_classe'];
        $nome_classe = trim($_POST['nome_classe']);
        $materie_selezionate = $_POST['materie'];
        $docenti_selezionati = $_POST['docenti'];

        if (!empty($nome_classe) && !empty($materie_selezionate) && !empty($docenti_selezionati)) {
            // Aggiorna il nome della classe
            $stmtClasse = $conn->prepare("UPDATE classi SET Nome = :nome_classe WHERE ID_Classe = :id_classe");
            $stmtClasse->execute([':nome_classe' => $nome_classe, ':id_classe' => $id_classe]);

            // Rimuovi tutte le materie e i docenti associati alla classe
            $conn->prepare("DELETE FROM classi_materie WHERE ID_C = :id_classe")->execute([':id_classe' => $id_classe]);
            $conn->prepare("DELETE FROM classi_docenti WHERE ID_C = :id_classe")->execute([':id_classe' => $id_classe]);

            // Inserisci le nuove associazioni tra classi e materie
            $stmtMaterie = $conn->prepare("INSERT INTO classi_materie (ID_C, ID_M) VALUES (:id_classe, :id_materia)");
            foreach ($materie_selezionate as $id_materia) {
                $stmtMaterie->execute([':id_classe' => $id_classe, ':id_materia' => $id_materia]);
            }

            // Inserisci le nuove associazioni tra classi e docenti
            $stmtDocenti = $conn->prepare("INSERT INTO classi_docenti (ID_C, ID_D) VALUES (:id_classe, :id_docente)");
            foreach ($docenti_selezionati as $id_docente) {
                $stmtDocenti->execute([':id_classe' => $id_classe, ':id_docente' => $id_docente]);
            }

            $success_message = "Classe modificata con successo!";
            header("Location: gestione_classi.php");
            exit;
        } else {
            $error_message = "Tutti i campi sono obbligatori.";
        }
    }

    // Gestione creazione classe (solo per root)
    if ($is_root && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crea_classe'])) {
        $nome_classe = trim($_POST['nome_classe']);
        $materie_selezionate = $_POST['materie'];
        $docenti_selezionati = $_POST['docenti'];

        if (!empty($nome_classe) && !empty($materie_selezionate) && !empty($docenti_selezionati)) {
            // Inserisce una nuova classe
            $stmtClasse = $conn->prepare("INSERT INTO classi (Nome) VALUES (:nome_classe)");
            $stmtClasse->execute([':nome_classe' => $nome_classe]);
            $id_classe = $conn->lastInsertId();

            // Associa le materie alla nuova classe
            $stmtMaterie = $conn->prepare("INSERT INTO classi_materie (ID_C, ID_M) VALUES (:id_classe, :id_materia)");
            foreach ($materie_selezionate as $id_materia) {
                $stmtMaterie->execute([':id_classe' => $id_classe, ':id_materia' => $id_materia]);
            }

            // Associa i docenti alla nuova classe
            $stmtDocenti = $conn->prepare("INSERT INTO classi_docenti (ID_C, ID_D) VALUES (:id_classe, :id_docente)");
            foreach ($docenti_selezionati as $id_docente) {
                $stmtDocenti->execute([':id_classe' => $id_classe, ':id_docente' => $id_docente]);
            }

            $success_message = "Classe creata con successo!";
            header("Location: gestione_classi.php");
            exit;
        } else {
            $error_message = "Tutti i campi sono obbligatori.";
        }
    }
	
	// Gestione impostazioni classe (solo per root)
if ($is_root && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['impostazioni_classe']) && isset($_POST['id_classe'])) {
    $id_classe = (int)$_POST['id_classe'];
    $ore_mattina = (int)$_POST['ore_mattina'];
    $ore_pomeriggio = (int)$_POST['ore_pomeriggio'];
    $ore_totali = (int)$_POST['ore_totali'];

    // Controlla se esistono già delle impostazioni per questa classe
    $stmt = $conn->prepare("SELECT ID_Setting FROM impostazioni_classe WHERE ID_Classe = :id_classe");
    $stmt->execute([':id_classe' => $id_classe]);
    $existingSetting = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSetting) {
        // Aggiorna le impostazioni esistenti
        $stmtImpostazioni = $conn->prepare("UPDATE impostazioni_classe SET Ore_Mattina = :ore_mattina, Ore_Pomeriggio = :ore_pomeriggio, Ore_Totali_Corso = :ore_totali WHERE ID_Classe = :id_classe");
        $stmtImpostazioni->execute([
            ':ore_mattina' => $ore_mattina,
            ':ore_pomeriggio' => $ore_pomeriggio,
            ':ore_totali' => $ore_totali,
            ':id_classe' => $id_classe
        ]);
    } else {
        // Inserisce nuove impostazioni per la classe
        $stmtImpostazioni = $conn->prepare("INSERT INTO impostazioni_classe (ID_Classe, Ore_Mattina, Ore_Pomeriggio, Ore_Totali_Corso) VALUES (:id_classe, :ore_mattina, :ore_pomeriggio, :ore_totali)");
        $stmtImpostazioni->execute([
            ':id_classe' => $id_classe,
            ':ore_mattina' => $ore_mattina,
            ':ore_pomeriggio' => $ore_pomeriggio,
            ':ore_totali' => $ore_totali
        ]);
    }

    $success_message = "Impostazioni della classe salvate con successo!";
    header("Location: gestione_classi.php");
    exit;
}

    // Gestione visualizzazione delle classi
    if ($is_root) {
        $queryClassi = $conn->query("
            SELECT classi.ID_Classe, classi.Nome, 
                   GROUP_CONCAT(DISTINCT materie.Materia SEPARATOR ', ') AS materie,
                   GROUP_CONCAT(DISTINCT docente.Username SEPARATOR ', ') AS docenti
            FROM classi
            LEFT JOIN classi_materie ON classi.ID_Classe = classi_materie.ID_C
            LEFT JOIN materie ON classi_materie.ID_M = materie.ID_Materia
            LEFT JOIN classi_docenti ON classi.ID_Classe = classi_docenti.ID_C
            LEFT JOIN docente ON classi_docenti.ID_D = docente.ID_Docente
            GROUP BY classi.ID_Classe
            ORDER BY classi.ID_Classe DESC
        ");
    } else {
        $queryClassi = $conn->prepare("
            SELECT classi.ID_Classe, classi.Nome, 
                   GROUP_CONCAT(DISTINCT materie.Materia SEPARATOR ', ') AS materie,
                   GROUP_CONCAT(DISTINCT docente.Username SEPARATOR ', ') AS docenti
            FROM classi
            LEFT JOIN classi_docenti ON classi.ID_Classe = classi_docenti.ID_C
            LEFT JOIN docente ON classi_docenti.ID_D = docente.ID_Docente
            LEFT JOIN classi_materie ON classi.ID_Classe = classi_materie.ID_C
            LEFT JOIN materie ON classi_materie.ID_M = materie.ID_Materia
            WHERE docente.ID_Docente = :user_id
            GROUP BY classi.ID_Classe
            ORDER BY classi.ID_Classe DESC
        ");
        $queryClassi->execute([':user_id' => $user_id]);
    }

    $classi = $queryClassi->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Classi</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleModificaForm(id) {
            var form = document.getElementById('modifica-form-' + id);
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'table-row' : 'none';
        }
    </script>
    <style>
        /* Aggiungi margine alla fine della tabella */
        .responsive-table {
            margin-bottom: 40px; /* Modifica la quantità di spazio a seconda delle tue necessità */
        }

        /* Aggiungi margine alla fine del contenitore */
        .container {
            margin-bottom: 50px; /* Aggiungi spazio extra sotto il contenitore */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestione Classi</h1>
        <div class="dashboard-button">
            <a href="dashboard.php" class="btn-primary">Torna alla Dashboard</a>
        </div>

        <form method="post" class="form-container">
            <h2>Nuova Classe</h2>
            <input type="hidden" name="crea_classe" value="1">
            
            <label for="nome_classe">Nome:</label>
            <input type="text" name="nome_classe" required placeholder="Inserisci il nome della classe">

            <label>Materie:</label>
            <select name="materie[]" multiple required>
                <?php foreach ($materie as $materia): ?>
                    <option value="<?= $materia['ID_Materia'] ?>"><?= htmlspecialchars($materia['Materia']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Docenti:</label>
            <select name="docenti[]" multiple required>
                <?php foreach ($docenti as $docente): ?>
                    <option value="<?= $docente['ID_Docente'] ?>"><?= htmlspecialchars($docente['Username']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Crea Classe</button>
        </form>

        <?php if (isset($success_message)) echo "<p class='success'>$success_message</p>"; ?>
        <?php if (isset($error_message)) echo "<p class='error'>$error_message</p>"; ?>

        <h2>Classi Esistenti</h2>
        <?php if ($classi): ?>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>ID Classe</th>
                        <th>Nome</th>
                        <th>Materie</th>
                        <th>Docenti</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classi as $classe): ?>
					 <?php
					// Recupera le impostazioni della classe corrente
					$stmt = $conn->prepare("SELECT Ore_Mattina, Ore_Pomeriggio, Ore_Totali_Corso FROM impostazioni_classe WHERE ID_Classe = :id_classe");
					$stmt->execute([':id_classe' => $classe['ID_Classe']]);
					$impostazioni = $stmt->fetch(PDO::FETCH_ASSOC);
					?>

                        <tr>
                            <td><?= $classe['ID_Classe'] ?></td>
                            <td><?= htmlspecialchars($classe['Nome']) ?></td>
                            <td><?= htmlspecialchars($classe['materie']) ?></td>
                            <td><?= htmlspecialchars($classe['docenti']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=delete&id=<?= $classe['ID_Classe'] ?>" class="btn-danger" onclick="return confirm('Eliminare questa classe?')">Elimina</a>
                                    <button class="btn-secondary" onclick="toggleModificaForm(<?= $classe['ID_Classe'] ?>)">Modifica</button>
                                    <button class="btn-settings" onclick="toggleImpostazioniForm(<?= $classe['ID_Classe'] ?>)">Impostazioni</button>
                                </div>
                            </td>
                        </tr>
                        <tr id="modifica-form-<?= $classe['ID_Classe'] ?>" style="display: none;">
                            <td colspan="5">
                                <form method="post" class="form-modifica">
                                    <input type="hidden" name="modifica_classe" value="1">
                                    <input type="hidden" name="id_classe" value="<?= $classe['ID_Classe'] ?>">

                                    <label for="nome_classe_<?= $classe['ID_Classe'] ?>">Nome:</label>
                                    <input type="text" name="nome_classe" value="<?= htmlspecialchars($classe['Nome']) ?>" required>

                                    <label>Materie:</label>
                                    <select name="materie[]" multiple required>
                                        <?php foreach ($materie as $materia): ?>
                                            <option value="<?= $materia['ID_Materia'] ?>" <?= in_array($materia['Materia'], explode(', ', $classe['materie'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($materia['Materia']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Docenti:</label>
                                    <select name="docenti[]" multiple required>
                                        <?php foreach ($docenti as $docente): ?>
                                            <option value="<?= $docente['ID_Docente'] ?>" <?= in_array($docente['Username'], explode(', ', $classe['docenti'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($docente['Username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-primary">Salva modifiche</button>
                                </form>
                            </td>
                        </tr>
                        
                        <tr id="impostazioni-form-<?= $classe['ID_Classe'] ?>" style="display: none;">
                            <td colspan="5">
                                <form method="post" class="form-impostazioni">
                                    <input type="hidden" name="impostazioni_classe" value="1">
                                    <input type="hidden" name="id_classe" value="<?= $classe['ID_Classe'] ?>">

                                    <label for="ore_mattina_<?= $classe['ID_Classe'] ?>">Ore Mattina:</label>
                                    <input type="number" name="ore_mattina" value="<?= isset($impostazioni['Ore_Mattina']) ? $impostazioni['Ore_Mattina'] : '' ?>" min="0" required>

                                    <label for="ore_pomeriggio_<?= $classe['ID_Classe'] ?>">Ore Pomeriggio:</label>
                                    <input type="number" name="ore_pomeriggio" value="<?= isset($impostazioni['Ore_Pomeriggio']) ? $impostazioni['Ore_Pomeriggio'] : '' ?>" min="0" required>

                                    <label for="ore_totali_<?= $classe['ID_Classe'] ?>">Ore Totali Corso:</label>
                                    <input type="number" name="ore_totali" value="<?= isset($impostazioni['Ore_Totali_Corso']) ? $impostazioni['Ore_Totali_Corso'] : '' ?>" min="0" required>
									<div style="margin-top: 20px;"></div>
                                    <button type="submit" class="btn-primary">Salva Impostazioni</button>
									
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleImpostazioniForm(id) {
            var form = document.getElementById('impostazioni-form-' + id);
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'table-row' : 'none';
        }
    </script>
</body>
</html>
