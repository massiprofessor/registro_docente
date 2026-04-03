<?php
require_once __DIR__ . '/db_connection.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $codice           = trim($_POST['codice'] ?? '');

    if ($password !== $confirm_password) {
        $error_message = "Le password non corrispondono!";
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $root = ($codice === "Accademia") ? "SI" : "NO";

        try {
            $stmt = $conn->prepare("INSERT INTO docente (Username, Email, Password, root) VALUES (:u, :e, :p, :r)");
            $stmt->execute([':u' => $username, ':e' => $email, ':p' => $password_hash, ':r' => $root]);
            header("Location: login_reg.html");
            exit;
        } catch (PDOException $ex) {
            $error_message = "Errore: " . $ex->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina di Registrazione</title>
    <!-- Collega il file CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="registration-container">
        <h2>Registrati</h2>

        <!-- Messaggio di errore, se presente -->
        <?php if ($error_message): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Form di registrazione -->
        <form action="" method="POST">
            <div class="form-group">
                <label for="username">Nome Utente</label>
                <input type="text" id="username" name="username" placeholder="Inserisci il tuo nome utente" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Inserisci la tua email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Inserisci la tua password" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Conferma Password</label>
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Conferma la tua password" required>
            </div>
            <div class="form-group">
                <label for="codice">Codice (Opzionale)</label>
                <input type="text" id="codice" name="codice" placeholder="Inserisci il codice 'Accademia' per ottenere l'accesso root">
            </div>
            <button type="submit" class="register-btn">Registrati</button>
        </form>

        <div class="login-link">
            <p>Hai già un account? <a href="login.html">Accedi</a></p>
        </div>
    </div>
</body>
</html>
