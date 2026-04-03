<?php
$host     = 'localhost';
$dbname   = 'regsx_class';
$username = 'INSERISCI USERNAME';
$password = 'INSERISCI PASSWORD';

// Connessione PDO (usata dai file moderni e dal modulo esami)
try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_uca1400_ai_ci"]
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage());
}

// Connessione mysqli (usata dai file legacy)
$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    die("Errore connessione mysqli: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
$mysqli->query("SET NAMES utf8mb4 COLLATE utf8mb4_uca1400_ai_ci");
