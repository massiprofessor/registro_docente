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
$docente_nome = $_SESSION['username']; // Assicurati che il nome del docente sia salvato nella sessione

// Funzioni utili
function getMaterie($conn) {
    $sql = "SELECT * FROM materie";
    $result = $conn->query($sql);
    return $result->fetchAll(PDO::FETCH_ASSOC);
}

function getArgomentiByMateria($conn, $materia_id) {
    $sql = "SELECT * FROM argomenti WHERE materia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Funzione per ottenere il nome della materia
function getMateriaName($conn, $materia_id) {
    $sql = "SELECT Materia FROM materie WHERE ID_Materia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id]);
    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
    return $materia ? $materia['Materia'] : 'Materia non trovata';
}

//PHP per gestire il modale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aggiungi nuovi argomenti
    if (isset($_POST['save_arguments'])) {
        $materia_id = $_POST['materia_id'];
        if (!empty($_POST['nuovi_argomenti'])) {
            $nuovi_argomenti = explode(',', $_POST['nuovi_argomenti']);
            foreach ($nuovi_argomenti as $argomento_nome) {
                $argomento_nome = trim($argomento_nome);
                if (!empty($argomento_nome)) {
                    $sql = "INSERT INTO argomenti (materia_id, nome) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$materia_id, $argomento_nome]);
                }
            }
        }
        header("Location: scheduling.php?materia_id=$materia_id");
        exit;
    }

    // Elimina un argomento
    if (isset($_POST['delete_argument'])) {
        $argomento_id = $_POST['delete_argument'];
        $sql = "DELETE FROM argomenti WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$argomento_id]);

        $materia_id = $_POST['materia_id'];
        header("Location: scheduling.php?materia_id=$materia_id");
        exit;
    }
}

// Funzione per eliminare scheduling e lezioni correlate
function deleteScheduling($conn, $scheduling_id) {
    try {
        $conn->beginTransaction();
        
        // Elimina dalle tabelle lezioni_argomenti e lezioni
        $sql1 = "DELETE FROM lezioni_argomenti WHERE scheduling_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([$scheduling_id]);
        
        $sql2 = "DELETE FROM lezioni WHERE scheduling_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$scheduling_id]);
        
        // Elimina lo scheduling dalla tabella scheduling_groups
        $sql3 = "DELETE FROM scheduling_groups WHERE id = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->execute([$scheduling_id]);
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

// Gestione della richiesta di eliminazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scheduling'])) {
    $scheduling_id = $_POST['delete_scheduling'];
    if (deleteScheduling($conn, $scheduling_id)) {
        echo "<div class='alert alert-success'>Scheduling eliminato con successo!</div>";
    } else {
        echo "<div class='alert alert-danger'>Errore durante l'eliminazione dello scheduling.</div>";
    }
}

// Recupera schedulings esistenti per una materia e una classe
function getSchedulingByMateriaAndClasse($conn, $materia_id, $classe_id) {
    $sql = "SELECT sg.id AS scheduling_id, sg.id, sg.materia_id, sg.classe_id, sg.nome_scheduling, sg.data_creazione, 
                   m.Materia AS materia_nome, c.Nome AS classe_nome, COUNT(l.id) AS numero_lezioni
            FROM scheduling_groups sg
            LEFT JOIN lezioni l ON sg.id = l.scheduling_id
            LEFT JOIN materie m ON sg.materia_id = m.ID_Materia
            LEFT JOIN classi c ON sg.classe_id = c.ID_Classe
            WHERE sg.materia_id = ? AND sg.classe_id = ?
            GROUP BY sg.id, sg.materia_id, sg.classe_id, sg.nome_scheduling, sg.data_creazione, m.Materia, c.Nome";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id, $classe_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLezioniByScheduling($conn, $materia_id, $classe_id, $id) {
    $sql = "SELECT l.id, l.numero_lezione, l.completato, l.commento, l.scheduling_id
FROM lezioni l
WHERE l.materia_id = ? AND l.classe_id = ? AND l.scheduling_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id, $classe_id,$id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





function getClassiByDocente($conn, $docente_id) {
    $sql = "SELECT c.ID_Classe, c.Nome
            FROM classi c
            INNER JOIN classi_docenti cd ON c.ID_Classe = cd.ID_C
            WHERE cd.ID_D = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$docente_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getArgomentiByLezioni($conn, $lezione_id, $scheduling_id) {
    $sql = "SELECT la.id AS argomento_id, a.nome, la.completato
            FROM lezioni_argomenti la
            JOIN argomenti a ON la.argomento_id = a.id
            WHERE la.lezione_id = ? AND la.scheduling_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$lezione_id, $scheduling_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//Funzione per recuperare argomenti associati
function getArgomentiAssociati($conn, $materia_id) {
    $sql = "SELECT id, nome FROM argomenti WHERE materia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$materia_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function toggleCompletato($conn, $lezione_id, $argomento_id) {
    // Controlla lo stato attuale
    $sql = "SELECT completato FROM lezioni_argomenti WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$argomento_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Inverte lo stato di completamento
    if ($result && $result['completato']) {
        $sql = "UPDATE lezioni_argomenti SET completato = 0 WHERE id = ?";
    } else {
        $sql = "UPDATE lezioni_argomenti SET completato = 1 WHERE id = ?";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute([$argomento_id]);

    // Aggiorna lo stato della lezione
    $sql = "SELECT COUNT(*) AS total, SUM(completato) AS completed
            FROM lezioni_argomenti
            WHERE lezione_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$lezione_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    $isCompleted = ($status['total'] == $status['completed']);
    $sql = "UPDATE lezioni SET completato = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$isCompleted ? 1 : 0, $lezione_id]);
}
// funzione per i toogle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_argument'])) {
    $lezione_id = $_POST['lezione_id'];
    $argomento_id = $_POST['argomento_id'];
    toggleCompletato($conn, $lezione_id, $argomento_id);
    exit;
}

// Funzione per associare materie a lezioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_arguments'])) {
        $materia_id = $_POST['materia_id'];

        if (!empty($_POST['nuovi_argomenti'])) {
            $nuovi_argomenti = explode(',', $_POST['nuovi_argomenti']);
            foreach ($nuovi_argomenti as $argomento_nome) {
                $argomento_nome = trim($argomento_nome);
                if (!empty($argomento_nome)) {
                    $sql = "INSERT INTO argomenti (materia_id, nome) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$materia_id, $argomento_nome]);
                }
            }
        }

        if (isset($_POST['argomenti_selezionati']) && !empty($_POST['argomenti_selezionati'])) {
            foreach ($_POST['argomenti_selezionati'] as $argomento_id) {
                $sql = "INSERT INTO lezioni_argomenti (materia_id, argomento_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$materia_id, $argomento_id]);
            }
        }

        $classe_id = $_GET['classe_id'] ?? '';

        if (empty($classe_id) && isset($_POST['classe_id'])) {
            $classe_id = $_POST['classe_id'];
        }

        header("Location: scheduling.php?materia_id=$materia_id&classe_id=$classe_id");
        exit;
    }
}

if (isset($_GET['delete_argument_id'])) {
    $argomento_id = $_GET['delete_argument_id'];
    $sql = "DELETE FROM argomenti WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$argomento_id]);

    $materia_id = $_GET['materia_id'];
    $classe_id = $_GET['classe_id'];
    header("Location: scheduling.php?materia_id=$materia_id&classe_id=$classe_id");
    exit;
}

// Creazione di un nuovo scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_scheduling'])) {
    $materia_id = $_POST['materia_id'];
    $classe_id = $_POST['classe_id'];
    $numero_lezioni = $_POST['numero_lezioni'];

    // Genera un nuovo scheduling_id basato sul nome della materia e del docente
    $nome_scheduling = "{$_POST['materia_id']} ({$docente_nome})";
    $query = "INSERT INTO scheduling_groups (materia_id, classe_id, docente_id, nome_scheduling) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$materia_id, $classe_id, $docente_id, $nome_scheduling]);
    $scheduling_id = $conn->lastInsertId();

    // Creazione delle lezioni associate a questo scheduling
    for ($i = 1; $i <= $numero_lezioni; $i++) {
        $sql = "INSERT INTO lezioni (materia_id, classe_id, numero_lezione, scheduling_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$materia_id, $classe_id, $i, $scheduling_id]);
    }

    echo "<div class='alert alert-success'>Scheduling creato con successo</div>";
}



$materie = getMaterie($conn);
$classi = getClassiByDocente($conn, $docente_id);
$materiaSelezionata = isset($_GET['materia_id']) ? $_GET['materia_id'] : null;
$classeSelezionata = isset($_GET['classe_id']) ? $_GET['classe_id'] : null;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Scheduling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
       function toggleDetails(schedulingId) {
    var details = document.getElementById('details-' + schedulingId);
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
    </script>
	

   <script>
    function toggleArgument(lezioneId, argomentoId, element) {
        fetch('scheduling.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `toggle_argument=1&lezione_id=${lezioneId}&argomento_id=${argomentoId}`
        }).then(response => {
            if (response.ok) {
                // Cambia lo stile dell'elemento cliccato
                if (element.classList.contains('bg-warning')) {
                    element.classList.remove('bg-warning');
                } else {
                    element.classList.add('bg-warning');
                }
            }
        });
    }
</script>
 <style>
        .badge {
            background-color: #f8f9fa; /* Sfondo chiaro */
            color: #212529; /* Colore del testo visibile */
            cursor: pointer; /* Mostra un cursore cliccabile */
        }
        .badge.bg-warning {
            background-color: #ffc107 !important; /* Giallo per i selezionati */
            color: #212529 !important; /* Testo scuro sul giallo */
        }
		
		 .modal-body-scrollable {
        max-height: 500px;
        overflow-y: auto;
    }
    </style>
	
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Gestione Scheduling</h1>
    <a href="dashboard.php" class="btn btn-secondary">Torna alla Dashboard</a>
</div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <label for="materia" class="form-label">Seleziona Materia:</label>
            <select name="materia_id" id="materia" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleziona --</option>
                <?php foreach ($materie as $materia): ?>
                    <option value="<?= $materia['ID_Materia'] ?>" <?= $materiaSelezionata == $materia['ID_Materia'] ? 'selected' : '' ?>>
                        <?= $materia['Materia'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="classe" class="form-label">Seleziona Classe:</label>
            <select name="classe_id" id="classe" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleziona --</option>
                <?php foreach ($classi as $classe): ?>
                    <option value="<?= $classe['ID_Classe'] ?>" <?= $classeSelezionata == $classe['ID_Classe'] ? 'selected' : '' ?>>
                        <?= $classe['Nome'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
		
		
		<?php if ($materiaSelezionata): ?>
        <div class="col-md-12 text-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignArgumentsModal">
                Gestisci Argomenti per <?= getMateriaName($conn, $materiaSelezionata) ?>
            </button>
        </div>
    <?php endif; ?>
    </form>

    <?php if ($materiaSelezionata && $classeSelezionata): ?>
        <div class="mb-4">
            <h2 class="h4">Crea uno Scheduling</h2>
            <form method="POST" class="row g-3">
                <input type="hidden" name="materia_id" value="<?= $materiaSelezionata ?>">
                <input type="hidden" name="classe_id" value="<?= $classeSelezionata ?>">
                <div class="col-md-6">
                    <label for="numero_lezioni" class="form-label">Numero di Lezioni:</label>
                    <input type="number" name="numero_lezioni" id="numero_lezioni" class="form-control" min="1" required>
                </div>
                <div class="col-md-6 align-self-end">
                    <button type="submit" name="add_scheduling" class="btn btn-primary">Crea Scheduling</button>
                </div>
            </form>
        </div>

        <div class="mb-4">
            <h2 class="h4">Schedulings Disponibili</h2>
            <ul class="list-group">
                <?php
                $schedulings = getSchedulingByMateriaAndClasse($conn, $materiaSelezionata, $classeSelezionata);
                foreach ($schedulings as $scheduling): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="javascript:void(0);" onclick="toggleDetails('<?= $scheduling['materia_id'] ?>-<?= $scheduling['classe_id'] ?>-<?= $scheduling['scheduling_id'] ?>')">
    Scheduling per <?= $scheduling['materia_nome'] ?> - Classe <?= $scheduling['classe_nome'] ?> - <?= $docente_nome ?>
</a>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
    <a href="gestione_scheduling.php?materia_id=<?= $scheduling['materia_id'] ?>&classe_id=<?= $scheduling['classe_id'] ?>&scheduling_id=<?= $scheduling['scheduling_id'] ?>" class="btn btn-primary btn-sm">Gestisci</a>
    <form method="POST">
        <input type="hidden" name="delete_scheduling" value="<?= $scheduling['scheduling_id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
    </form>
</div>
                    </li>
                    <div id="details-<?= $scheduling['materia_id'] ?>-<?= $scheduling['classe_id'] ?>-<?= $scheduling['scheduling_id']?>" style="display:none;">
                        <?php
                        $lezioni = getLezioniByScheduling($conn, $scheduling['materia_id'], $scheduling['classe_id'], $scheduling['id']);
                        ?>
                        <h4>Dettagli Scheduling</h4>
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Numero Lezione</th>
                                    <th>Argomento</th>
                                    <th>Completato</th>
                                    <th>Commento</th>
                                </tr>
                            </thead>
           <tbody>
    <?php foreach ($lezioni as $lezione): ?>
        <?php $arg = getArgomentiByLezioni($conn, $lezione['id'],$lezione['scheduling_id'] ); ?>
        <tr>
            <td><?= $lezione['numero_lezione'] ?></td>
            <td>
                <?php 
                if (!empty($arg)) {
                    foreach ($arg as $item) {
                        $argomento_id = $item['argomento_id']; // ID della riga in `lezioni_argomenti`
                        $argomento_nome = $item['nome']; // Nome dell'argomento
                        $completato = $item['completato']; // Stato di completamento
                        ?>
                        <span 
                            class="badge <?= $completato ? 'bg-warning' : '' ?>" 
                            onclick="toggleArgument(<?= $lezione['id'] ?>, <?= $argomento_id ?>, this)">
                            <?= $argomento_nome ?>
                        </span>
                        <?php
                    }
                } else {
                    echo 'Nessun argomento';
                }
                ?>
            </td>
            <td><?= $lezione['completato'] ? 'Sì' : 'No' ?></td>
            <td><?= $lezione['commento'] ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

                        </table>
                    </div>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p class="text-danger">Seleziona una materia e una classe per visualizzare le opzioni.</p>
    <?php endif; ?>
	

<!-- Modale Associa Argomenti -->
<div class="modal fade" id="assignArgumentsModal" tabindex="-1" aria-labelledby="assignArgumentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignArgumentsModalLabel">Gestisci Argomenti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="scheduling.php">
                <div class="modal-body modal-body-scrollable">
                    <!-- Materia -->
                    <div class="mb-3">
                        <label for="materia" class="form-label">Materia Selezionata:</label>
                        <input type="text" class="form-control" value="<?= getMateriaName($conn, $materiaSelezionata) ?>" readonly>
                        <input type="hidden" name="materia_id" value="<?= $materiaSelezionata ?>">
                    </div>
                    
                    <!-- Nuovi Argomenti -->
                    <div class="mb-3">
                        <label for="nuovi_argomenti" class="form-label">Aggiungi Nuovi Argomenti (separati da virgola):</label>
                        <input type="text" class="form-control" name="nuovi_argomenti" id="nuovi_argomenti" placeholder="Argomento1, Argomento2">
                    </div>

                    <!-- Argomenti Esistenti -->
                    <div class="mb-3">
                        <label for="argomenti_esistenti" class="form-label">Argomenti Esistenti:</label>
                        <div class="row">
                            <?php 
                            $argomenti = getArgomentiAssociati($conn, $materiaSelezionata);
                            $chunks = array_chunk($argomenti, 10); // Divide gli argomenti in gruppi di 10
                            foreach ($chunks as $chunk): ?>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <ul class="list-group">
                                        <?php foreach ($chunk as $argomento): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= $argomento['nome'] ?>
                                                <button type="submit" name="delete_argument" value="<?= $argomento['id'] ?>" class="btn btn-danger btn-sm">Elimina</button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <button type="submit" name="save_arguments" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>




	
	
	
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
