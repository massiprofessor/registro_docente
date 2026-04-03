<?php
// Avvia la sessione
session_start();

// Cancella tutte le variabili di sessione
$_SESSION = [];

// Se desideri distruggere il cookie della sessione (opzionale)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Reindirizza l'utente alla pagina di login o homepage
header("Location: login.html");
exit;
?>