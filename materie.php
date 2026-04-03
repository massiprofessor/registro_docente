<?php
session_start();
if (!isset($_SESSION['User_id'])) {
    header("Location: login.html");
    exit;
}
require_once __DIR__ . '/db_connection.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Nome Materia</title>
    <!-- Aggiungi Bootstrap e Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
        }

        .form-materia {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 50px auto;
        }

        .form-materia h1 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group-materia label {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group-materia input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-group-materia button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-group-materia button:hover {
            background-color: #0056b3;
        }

        .back-button {
            margin-top: 20px;
            text-align: center;
        }

        .back-button a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .back-button a:hover {
            text-decoration: underline;
        }

        .back-button img {
            width: 20px; /* Ridotto per icona piccola */
            height: auto;
            margin-right: 5px;
        }

        .elenco-materie {
            margin-top: 40px;
        }

        .elenco-materie h2 {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .materia-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background-color: #f1f1f1;
            border-radius: 5px;
        }

        .materia-item button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .materia-item button:hover {
            background-color: #c82333;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="form-materia">
            <h1>Inserisci Materia</h1>
            <form action="elabora_materia.php" method="post">
                <div class="form-group-materia">
                    <label for="nome_materia">Nome Materia:</label>
                    <input type="text" id="nome_materia" name="nome_materia" required>
                </div>
                <div class="form-group-materia">
                    <button type="submit">Invia</button>
                </div>
                <div class="back-button">
                    <a href="dashboard.php">
                        <img src="icons/back-icon.png" alt="Torna indietro"> Torna alla Pagina Principale
                    </a>
                </div>
            </form>

            <!-- Elenco delle materie -->
            <div class="elenco-materie">
                <h2>Materie Disponibili</h2>
                <?php include 'elenco_materie.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>