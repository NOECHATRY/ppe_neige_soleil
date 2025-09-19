<?php
// On inclut le fichier qui permet de se connecter à la base de données
require_once('../BDD/connect_bdd.php');

// On établit la connexion à la base de données
$pdo = bdd();

// Variables importantes pour le fonctionnement de la page
$contrat_a_modifier = null;   // Contiendra les infos du contrat qu'on veut modifier
$modification_mode = false;   // Indique si on est en train de modifier un contrat (true) ou d'en ajouter un nouveau (false)
$message = '';                // Message à afficher à l'utilisateur (succès ou erreur)

// Si le formulaire a été soumis (méthode POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // On regarde quelle action a été demandée
    $action = $_POST['action'] ?? '';
    
    // Selon l'action, on appelle la fonction appropriée
    if ($action == 'delete' && !empty($_POST['contrat'])) {
        $message = supprimerContrat($pdo);
    }
    elseif ($action == 'update') {
        $message = modifierContrat($pdo);
        $modification_mode = false;  // Après modification, on quitte le mode modification
    }
    elseif ($action == 'add') {
        $message = ajouterContrat($pdo);
    }
}

// Si on a cliqué sur "modifier" un contrat
if (isset($_GET['modifier'])) {
    // On récupère les infos du contrat à modifier
    $contrat = $pdo->prepare("SELECT c.NumC, c.DateC, c.numA, c.numC_1, cl.Nom, cl.Prenom 
                              FROM contratdelocation c JOIN client cl ON c.numC_1 = cl.numC 
                              WHERE c.NumC = ?");
    $contrat->execute([(int)$_GET['modifier']]);
    $contrat_a_modifier = $contrat->fetch();
    
    // Si le contrat existe, on active le mode modification
    $modification_mode = ($contrat_a_modifier !== false);
}

// Fonction pour supprimer un contrat
function supprimerContrat($pdo) {
    // On récupère l'ID du contrat à supprimer
    $id = (int)$_POST['contrat'];
    
    // D'abord, on supprime les références dans la table "appartenir"
    // (pour éviter les problèmes de clés étrangères)
    $pdo->prepare("DELETE FROM appartenir WHERE NumC = ?")->execute([$id]);
    
    // Ensuite, on supprime le contrat lui-même
    $pdo->prepare("DELETE FROM contratdelocation WHERE NumC = ?")->execute([$id]);
    
    // On retourne un message de confirmation
    return "✅ Contrat supprimé !";
}

// Fonction pour modifier un contrat existant
function modifierContrat($pdo) {
    try {
        // On prépare et exécute la requête de modification
        $pdo->prepare("UPDATE contratdelocation SET DateC = ?, numC_1 = ? WHERE NumC = ?")
            ->execute([$_POST['dateC'], (int)$_POST['numC_1'], (int)$_POST['numC']]);
        
        // Message de confirmation
        return "✅ Contrat modifié !";
    } catch (Exception $e) {
        // En cas d'erreur, on affiche un message d'erreur
        return "❌ Erreur lors de la modification: " . $e->getMessage();
    }
}

// Fonction pour ajouter un nouveau contrat
function ajouterContrat($pdo) {
    try {
        // On récupère le numéro d'appartement
        $numA = (int)$_POST['numA'];
        
        // On vérifie si cet appartement est déjà sous contrat
        $count = $pdo->prepare("SELECT COUNT(*) FROM contratdelocation WHERE numA = ?");
        $count->execute([$numA]);
        
        // Si l'appartement est déjà loué
        if ($count->fetchColumn() > 0) {
            return "❌ Appartement déjà sous contrat !";
        } else {
            // On trouve le prochain numéro de contrat disponible
            $max = $pdo->query("SELECT MAX(NumC) FROM contratdelocation")->fetchColumn();
            
            // On insère le nouveau contrat dans la base de données
            $pdo->prepare("INSERT INTO contratdelocation (NumC, DateC, numA, numC_1) VALUES (?, ?, ?, ?)")
                ->execute([$max + 1, $_POST['dateC'], $numA, (int)$_POST['numC_1']]);
            
            // Message de confirmation
            return "✅ Contrat ajouté !";
        }
    } catch (Exception $e) {
        // En cas d'erreur, on affiche un message d'erreur
        return "❌ Erreur lors de l'ajout: " . $e->getMessage();
    }
}

// On récupère les données nécessaires pour afficher la page

// Tous les contrats existants
$contrats = $pdo->query("
    SELECT c.NumC, c.DateC, a.numA, cl.Nom, cl.Prenom
    FROM contratdelocation c
    JOIN appartement a ON c.numA = a.numA
    JOIN client cl ON c.numC_1 = cl.numC
    ORDER BY c.DateC DESC
")->fetchAll();

// Appartements disponibles (non loués)
$appartements_disponibles = $pdo->query("
    SELECT numA FROM appartement 
    WHERE numA NOT IN (SELECT numA FROM contratdelocation)
    ORDER BY numA
")->fetchAll();

// Tous les appartements (pour les afficher dans la liste)
$tous_appartements = $pdo->query("SELECT numA FROM appartement ORDER BY numA")->fetchAll();

// Tous les locataires (pour les listes déroulantes)
$locataires = $pdo->query("SELECT l.numC, c.Nom, c.Prenom FROM locataire l JOIN client c ON l.numC = c.numC ORDER BY c.Nom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des contrats</title>
</head>
<body>
    <h2>Gestion des contrats</h2>
    
    <!-- On affiche un message si une action a été effectuée -->
    <?php if (!empty($message)): ?>
        <div><?= $message ?></div>
    <?php endif; ?>
    
    <!-- FORMULAIRE DE MODIFICATION (apparaît seulement quand on modifie un contrat) -->
    <?php if ($modification_mode && $contrat_a_modifier): ?>
        <h3>Modifier le contrat #<?= $contrat_a_modifier['NumC'] ?></h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="numC" value="<?= $contrat_a_modifier['NumC'] ?>">
            
            <label>Date: <input type="date" name="dateC" value="<?= $contrat_a_modifier['DateC'] ?>" required></label><br>
            <label>Appartement: Appartement <?= $contrat_a_modifier['numA'] ?></label><br>
            
            <label>Locataire:
                <select name="numC_1" required>
                    <option value="">-- Sélectionnez --</option>
                    <!-- Liste de tous les locataires -->
                    <?php foreach ($locataires as $loc): ?>
                        <!-- On pré-sélectionne le locataire actuel -->
                        <option value="<?= $loc['numC'] ?>" <?= $loc['numC'] == $contrat_a_modifier['numC_1'] ? 'selected' : '' ?>>
                            <?= $loc['Prenom'] ?> <?= $loc['Nom'] ?> (ID: <?= $loc['numC'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            
            <button type="submit">Modifier</button>
            <a href="gestion_contrat.php">Annuler</a>
        </form>
    <?php else: ?>
        <!-- FORMULAIRE D'AJOUT (apparaît quand on n'est pas en mode modification) -->
        <h3>Ajouter un contrat</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <label>Date: <input type="date" name="dateC" value="<?= date('Y-m-d') ?>" required></label><br>
            
            <label>Appartement:
                <select name="numA" required>
                    <option value="">-- Sélectionnez --</option>
                    <!-- Appartements disponibles -->
                    <?php foreach ($appartements_disponibles as $apt): ?>
                        <option value="<?= $apt['numA'] ?>">Appartement <?= $apt['numA'] ?> (Disponible)</option>
                    <?php endforeach; ?>
                    
                    <!-- Appartements déjà loués (affichés mais non sélectionnables) -->
                    <?php foreach ($tous_appartements as $apt): 
                        // On vérifie si l'appartement est déjà sous contrat
                        $count = $pdo->prepare("SELECT COUNT(*) FROM contratdelocation WHERE numA = ?");
                        $count->execute([$apt['numA']]);
                        if ($count->fetchColumn() > 0): ?>
                            <option value="<?= $apt['numA'] ?>" disabled>Appartement <?= $apt['numA'] ?> (Déjà sous contrat)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label><br>
            
            <label>Locataire:
                <select name="numC_1" required>
                    <option value="">-- Sélectionnez --</option>
                    <!-- Liste de tous les locataires -->
                    <?php foreach ($locataires as $loc): ?>
                        <option value="<?= $loc['numC'] ?>"><?= $loc['Prenom'] ?> <?= $loc['Nom'] ?> (ID: <?= $loc['numC'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label><br>
            
            <button type="submit">Ajouter</button>
        </form>
    <?php endif; ?>
    
    <!-- TABLEAU QUI AFFICHE TOUS LES APPARTEMENTS ET LEUR STATUT -->
    <h3>Liste des appartements</h3>
    <table border="1">
        <tr>
            <th>Appartement</th>
            <th>Statut</th>
            <th>Contrat #</th>
            <th>Locataire</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <!-- Pour chaque appartement, on affiche une ligne dans le tableau -->
        <?php foreach ($tous_appartements as $apt): 
            // On récupère les infos du contrat pour cet appartement (s'il existe)
            $contrat_info = $pdo->prepare("
                SELECT c.NumC, c.DateC, cl.Nom, cl.Prenom 
                FROM contratdelocation c 
                JOIN client cl ON c.numC_1 = cl.numC 
                WHERE c.numA = ?
            ");
            $contrat_info->execute([$apt['numA']]);
            $info = $contrat_info->fetch();
            ?>
            <tr>
                <td><?= $apt['numA'] ?></td>
                <!-- On affiche en rouge si sous contrat, en vert si disponible -->
                <td><?= $info ? '<span style="color:red;">Sous contrat</span>' : '<span style="color:green;">Disponible</span>' ?></td>
                <td><?= $info['NumC'] ?? '-' ?></td>
                <td><?= $info ? $info['Prenom'] . ' ' . $info['Nom'] : '-' ?></td>
                <td><?= $info['DateC'] ?? '-' ?></td>
                <!-- Lien pour modifier (seulement si l'appartement est sous contrat) -->
                <td><?= $info ? '<a href="?modifier=' . $info['NumC'] . '">Modifier</a>' : '-' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- FORMULAIRE POUR SUPPRIMER UN CONTRAT -->
    <h3>Supprimer un contrat</h3>
    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat?');">
        <input type="hidden" name="action" value="delete">
        
        <label>Sélectionnez un contrat:
            <select name="contrat" required>
                <option value="">-- Sélectionnez --</option>
                <!-- Liste de tous les contrats existants -->
                <?php foreach ($contrats as $ct): ?>
                    <option value="<?= $ct['NumC'] ?>">
                        Contrat #<?= $ct['NumC'] ?> - Appartement <?= $ct['numA'] ?> - 
                        <?= $ct['Prenom'] ?> <?= $ct['Nom'] ?> (<?= $ct['DateC'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        
        <button type="submit">Supprimer</button>
    </form>
</body>
</html>