<?php
session_start(); // Avvia la sessione

// Controlla se l'utente è loggato
if (!isset($_SESSION['User_id'])) {
    // Se l'utente non è loggato, reindirizza alla pagina di login
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/db_connection.php';

// Recupera i dettagli dell'utente
$user_id = $_SESSION['User_id'];
$stmt = $conn->prepare("SELECT Username, root FROM docente WHERE ID_Docente = :id");
$stmt->execute([':id' => $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $row['Username'] ?? "Utente sconosciuto";
$root     = $row['root'] ?? "NO";

// Verifica se l'utente ha privilegi di amministratore (root)
if ($root !== "SI") {
    // Se l'utente non è root, mostra la pagina di accesso negato
    echo "
    <!DOCTYPE html>
    <html lang='it'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Accesso Negato</title>
        <link rel='stylesheet' href='style.css'> <!-- Collegamento al file CSS -->
    </head>
    <body>
        <div class='access-denied-container'>
            <h1>Accesso Negato</h1>
            <p>Ciao " . htmlspecialchars($username) . ",</p>
            <p>Non sei autorizzato a visualizzare questa pagina. La tua sessione non ha i privilegi di amministratore (root).</p>
            <p>Se pensi di aver ricevuto questo messaggio per errore, contatta l'amministratore del sistema.</p>
            <div class='back-button'>
                <a href='dashboard.php'>Torna alla Pagina Principale</a>
            </div>
        </div>
    </body>
    </html>
    ";
    exit;
}

// Chiudi connessione
?>
