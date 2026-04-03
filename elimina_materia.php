<?php
require_once __DIR__ . '/db_connection.php';

// Controllo del metodo POST e dell'esistenza del campo 'id_materia'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_materia'])) {
    $idMateria = (int)$_POST['id_materia'];
    $conn->prepare("DELETE FROM classi_materie WHERE ID_M = :id")->execute([':id' => $idMateria]);
    $conn->prepare("DELETE FROM materie WHERE ID_Materia = :id")->execute([':id' => $idMateria]);
    header('Location: materie.php');
    exit;
} else {
    echo "Richiesta non valida.";
}
?>