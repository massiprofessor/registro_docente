<?php
session_start();
require_once __DIR__ . '/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM docente WHERE Email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['User_id']  = $user['ID_Docente'];
        $_SESSION['username'] = $user['Username'];
        header("Location: dashboard.php");
        exit;
    } elseif ($user) {
        echo "<p style='color:red;text-align:center'>Password errata. <a href='login.html'>Riprova</a></p>";
    } else {
        echo "<p style='color:red;text-align:center'>Email non trovata. <a href='login.html'>Riprova</a></p>";
    }
} else {
    header("Location: login.html");
    exit;
}
?>
