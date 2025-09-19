<?php
require_once('BDD/connect_bdd.php');
$pdo = bdd();

// Initialisation des variables pour la modification
$contrat_a_modifier = null;
$modification_mode = false;

// --- Suppression si formulaire soumis ---
if (!empty($_POST['contrat']) && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int) $_POST['contrat'];

    // Supprimer d'abord dans "appartenir" à cause des clés étrangères
    $stmt = $pdo->prepare("DELETE FROM appartenir WHERE NumC = :id");
    $stmt->execute(['id' => $id]);

    // Supprimer dans contratdelocation
    $stmt = $pdo->prepare("DELETE FROM contratdelocation WHERE NumC = :id");
    $stmt->execute(['id' => $id]);

    echo "<p class='alert alert-success'>✅ Contrat supprimé avec succès !</p>";
}

// --- Modification si formulaire soumis ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $numC = (int) $_POST['numC'];
    $dateC = $_POST['dateC'];
    $numC_1 = (int) $_POST['numC_1'];

    try {
        // Mettre à jour le contrat
        $stmt = $pdo->prepare("UPDATE contratdelocation SET DateC = :dateC, numC_1 = :numC_1 WHERE NumC = :numC");
        $stmt->execute([
            'numC' => $numC,
            'dateC' => $dateC,
            'numC_1' => $numC_1
        ]);

        echo "<p style='color:green;'>✅ Contrat modifié avec succès !</p>";
        $modification_mode = false;
    } catch (PDOException $e) {
        echo "<p class='alert alert-error'>❌ Erreur lors de la modification du contrat: " . $e->getMessage() . "</p>";
    }
}

// --- Activation du mode modification ---
if (isset($_GET['modifier'])) {
    $numC = (int) $_GET['modifier'];

    // Récupérer les informations du contrat à modifier
    $stmt = $pdo->prepare("
        SELECT c.NumC, c.DateC, c.numA, c.numC_1, cl.Nom, cl.Prenom 
        FROM contratdelocation c 
        JOIN client cl ON c.numC_1 = cl.numC 
        WHERE c.NumC = :numC
    ");
    $stmt->execute(['numC' => $numC]);
    $contrat_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($contrat_a_modifier) {
        $modification_mode = true;
    }
}

// --- Ajout si formulaire soumis ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $dateC = $_POST['dateC'];
    $numA = (int) $_POST['numA'];
    $numC_1 = (int) $_POST['numC_1'];

    try {
        // Vérifier si l'appartement est déjà sous contrat
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contratdelocation WHERE numA = :numA");
        $stmt->execute(['numA' => $numA]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo "<p style='color:red;'>❌ Cet appartement est déjà sous contrat !</p>";
        } else {
            // Trouver le prochain numéro de contrat disponible
            $stmt = $pdo->query("SELECT MAX(NumC) as max_num FROM contratdelocation");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = $result['max_num'] + 1;

            // Insérer le nouveau contrat
            $stmt = $pdo->prepare("INSERT INTO contratdelocation (NumC, DateC, numA, numC_1) VALUES (:numC, :dateC, :numA, :numC_1)");
            $stmt->execute([
                'numC' => $nextNum,
                'dateC' => $dateC,
                'numA' => $numA,
                'numC_1' => $numC_1
            ]);

            echo "<p style='color:green;'>✅ Contrat ajouté avec succès !</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Erreur lors de l'ajout du contrat: " . $e->getMessage() . "</p>";
    }
}

// --- Récupération des contrats ---
$sql = "SELECT c.NumC, c.DateC, a.numA, cl.Nom, cl.Prenom
        FROM contratdelocation c
        JOIN appartement a ON c.numA = a.numA
        JOIN client cl ON c.numC_1 = cl.numC
        ORDER BY c.DateC DESC";
$stmt = $pdo->query($sql);
$contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Récupération des appartements disponibles (non sous contrat) ---
$appartements_disponibles = $pdo->query("
    SELECT a.numA 
    FROM appartement a 
    WHERE a.numA NOT IN (SELECT numA FROM contratdelocation)
    ORDER BY a.numA
")->fetchAll(PDO::FETCH_ASSOC);

// --- Récupération de tous les appartements (pour information) ---
$tous_appartements = $pdo->query("SELECT numA FROM appartement ORDER BY numA")->fetchAll(PDO::FETCH_ASSOC);

// --- Récupération des locataires pour le formulaire d'ajout ---
$locataires = $pdo->query("SELECT l.numC, c.Nom, c.Prenom FROM locataire l JOIN client c ON l.numC = c.numC ORDER BY c.Nom, c.Prenom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des contrats</title>
    <link rel="stylesheet" href="../../Frontend/css/style.css">
</head>

<body>
    <h2>Gestion des contrats</h2>

    <?php if ($modification_mode && $contrat_a_modifier): ?>
        <h2>Modifier le contrat #<?= $contrat_a_modifier['NumC'] ?></h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="numC" value="<?= $contrat_a_modifier['NumC'] ?>">

            <label for="dateC">Date du contrat:</label>
            <input type="date" name="dateC" id="dateC" required value="<?= $contrat_a_modifier['DateC'] ?>">
            <br><br>

            <label for="numA">Appartement:</label>
            <input type="text" value="Appartement <?= $contrat_a_modifier['numA'] ?>" disabled>
            <input type="hidden" name="numA" value="<?= $contrat_a_modifier['numA'] ?>">
            <br><br>

            <label for="numC_1">Locataire:</label>
            <select name="numC_1" id="numC_1" required>
                <option value="">-- Sélectionnez un locataire --</option>
                <?php foreach ($locataires as $locataire): ?>
                    <option value="<?= $locataire['numC'] ?>" <?= ($locataire['numC'] == $contrat_a_modifier['numC_1']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($locataire['Prenom'] . " " . $locataire['Nom']) ?> (ID: <?= $locataire['numC'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <button type="submit">Modifier le contrat</button>
            <a href="gestion_contrat.php">Annuler</a>
        </form>

        <hr>

    <?php else: ?>

        <h2>Ajouter un nouveau contrat</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <label for="dateC">Date du contrat:</label>
            <input type="date" name="dateC" id="dateC" required value="<?= date('Y-m-d') ?>">
            <br><br>

            <label for="numA">Appartement:</label>
            <select name="numA" id="numA" required>
                <option value="">-- Sélectionnez un appartement --</option>
                <?php foreach ($appartements_disponibles as $appartement): ?>
                    <option value="<?= $appartement['numA'] ?>">Appartement <?= $appartement['numA'] ?> (Disponible)</option>
                <?php endforeach; ?>

                <?php foreach ($tous_appartements as $appartement):
                    // Vérifier si l'appartement est déjà sous contrat
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM contratdelocation WHERE numA = :numA");
                    $stmt->execute(['numA' => $appartement['numA']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result['count'] > 0): ?>
                        <option value="<?= $appartement['numA'] ?>" disabled style="color:red;">
                            Appartement <?= $appartement['numA'] ?> (Déjà sous contrat)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="numC_1">Locataire:</label>
            <select name="numC_1" id="numC_1" required>
                <option value="">-- Sélectionnez un locataire --</option>
                <?php foreach ($locataires as $locataire): ?>
                    <option value="<?= $locataire['numC'] ?>">
                        <?= htmlspecialchars($locataire['Prenom'] . " " . $locataire['Nom']) ?> (ID: <?= $locataire['numC'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <button type="submit">Ajouter le contrat</button>
        </form>

        <hr>

    <?php endif; ?>

    <h2>Liste des appartements et leur statut</h2>
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <tr>
            <th>Appartement</th>
            <th>Statut</th>
            <th>Contrat #</th>
            <th>Locataire</th>
            <th>Date du contrat</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($tous_appartements as $appartement):
            $stmt = $pdo->prepare("
                SELECT c.NumC, c.DateC, cl.Nom, cl.Prenom 
                FROM contratdelocation c 
                JOIN client cl ON c.numC_1 = cl.numC 
                WHERE c.numA = :numA
            ");
            $stmt->execute(['numA' => $appartement['numA']]);
            $contrat_info = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <tr>
                <td align="center"><?= $appartement['numA'] ?></td>
                <td>
                    <?php if ($contrat_info): ?>
                        <span style="color:red;">Sous contrat</span>
                    <?php else: ?>
                        <span style="color:green;">Disponible</span>
                    <?php endif; ?>
                </td>
                <td align="center"><?= $contrat_info['NumC'] ?? '-' ?></td>
                <td>
                    <?php if ($contrat_info): ?>
                        <?= htmlspecialchars($contrat_info['Prenom'] . ' ' . $contrat_info['Nom']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td align="center"><?= $contrat_info['DateC'] ?? '-' ?></td>
                <td align="center">
                    <?php if ($contrat_info): ?>
                        <a href="?modifier=<?= $contrat_info['NumC'] ?>">Modifier</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <hr>

    <h2>Supprimer un contrat existant</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="delete">

        <label for="contrat">Choisis un contrat à supprimer :</label>
        <select name="contrat" id="contrat" required>
            <option value="">-- Sélectionne --</option>
            <?php foreach ($contrats as $contrat): ?>
                <option value="<?= $contrat['NumC'] ?>">
                    Contrat #<?= $contrat['NumC'] ?> -
                    Appartement <?= $contrat['numA'] ?> -
                    <?= htmlspecialchars($contrat['Prenom'] . " " . $contrat['Nom']) ?>
                    (<?= $contrat['DateC'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" onclick="return confirm('⚠️ Es-tu sûr de vouloir supprimer ce contrat ?');">
            Supprimer
        </button>
    </form>
</body>

</html>