<?php
require_once __DIR__ . '/db_connection.php';
try {

    // Controlla se il modulo è stato inviato
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome_materia = trim($_POST['nome_materia']); // Recupera e pulisce il dato

        // Controlla che il campo non sia vuoto
        if (!empty($nome_materia)) {
            // Query SQL per inserire il dato
            $stmt = $conn->prepare("INSERT INTO materie (Materia) VALUES (:nome_materia)");
            $stmt->bindParam(':nome_materia', $nome_materia, PDO::PARAM_STR);
            $stmt->execute();
			header("Location: materie.php");
        } else {
            echo "Il campo Nome Materia è vuoto!";
        }
    }
} catch (PDOException $e) {
    echo "Errore nella connessione al database: " . $e->getMessage();
}
?>