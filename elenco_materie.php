<?php
// Questo file viene incluso da materie.php — NON ha una sua connessione né session
// $conn è già disponibile dal file padre
$stmt_elenco = $conn->query("SELECT * FROM materie ORDER BY ID_Materia DESC");
$materie_elenco = $stmt_elenco->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="materie-list">
    <?php if (empty($materie_elenco)): ?>
        <p class="text-muted text-center">Nessuna materia inserita.</p>
    <?php else: ?>
        <?php foreach ($materie_elenco as $row): ?>
            <div class="materie-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                <h5 class="mb-0"><?= htmlspecialchars($row['Materia']) ?></h5>
                <form action="elimina_materia.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id_materia" value="<?= $row['ID_Materia'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Elimina</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

