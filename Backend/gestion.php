<?php
require_once "BDD/connect_bdd.php";
require_once "gestion_formulaires.php";
$bdd = bdd();


// Traitement formulaire
$message = handleFormActions($bdd);

// Récupération des données
$data = fetchAllData($bdd);
$locataires = $data['locataires'];
$tous_appartements = $data['appartements'];
$contrats = $data['contrats'];
$expositions = $data['expositions'];
$types = $data['types'];
$proprietaires = $data['proprietaires'];

// Mode modification
$modification_mode = false;
$contrat_a_modifier = null;
if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    $modification_mode = true;
    $id = (int) $_GET['modifier'];
    try {
        $stmt = $bdd->prepare("SELECT * FROM contratdelocation WHERE NumC = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $contrat_a_modifier = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        echo "<p>Erreur lecture contrat à modifier : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Neige & Soleil</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h2 { margin-top: 40px; }
        form { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; background: #f9f9f9; }
        input, select { margin: 5px; padding: 5px; }
        .msg { font-weight: bold; margin-bottom: 15px; color: green; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Neige & Soleil</h1>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- === LOCATAIRES === -->
    <h2>Locataires</h2>

    <h4>Ajouter un locataire</h4>
    <form method="post">
        <input type="hidden" name="action" value="addLocataire">
        <input type="text" name="Nom" placeholder="Nom" required>
        <input type="text" name="Prenom" placeholder="Prénom" required>
        <input type="text" name="Adresse" placeholder="Adresse"><br>
        <input type="email" name="Mail" placeholder="Email">
        <input type="text" name="TelF" placeholder="Tel Fixe">
        <input type="text" name="TelP" placeholder="Tel Portable">
        <button type="submit">Ajouter Locataire</button>
    </form>

    <h4>Modifier un locataire</h4>
    <form method="post">
        <input type="hidden" name="action" value="updateloc">
        <label>Sélectionnez un locataire :</label>
        <select name="numC" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($locataires as $loc): ?>
                <option value="<?= $loc['numC'] ?>"><?= htmlspecialchars($loc['Nom'] . " " . $loc['Prenom']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <input type="text" name="Nom" placeholder="Nom">
        <input type="text" name="Prenom" placeholder="Prénom">
        <input type="text" name="Adresse" placeholder="Adresse"><br>
        <input type="email" name="Mail" placeholder="Email">
        <input type="text" name="TelF" placeholder="Tel Fixe">
        <input type="text" name="TelP" placeholder="Tel Portable">
        <button type="submit">Modifier Locataire</button>
    </form>

    <h4>Supprimer un locataire</h4>
    <form method="post">
        <input type="hidden" name="action" value="delloc">
        <label>Sélectionnez un locataire :</label>
        <select name="numC" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($locataires as $loc): ?>
                <option value="<?= $loc['numC'] ?>"><?= htmlspecialchars($loc['Nom'] . " " . $loc['Prenom']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Supprimer Locataire</button>
    </form>

    <!-- === CONTRATS === -->
    <?php if ($modification_mode && $contrat_a_modifier): ?>
        <h2>Modifier le contrat #<?= $contrat_a_modifier['NumC'] ?></h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="numC" value="<?= $contrat_a_modifier['NumC'] ?>">
            <label>Date du contrat:</label>
            <input type="date" name="dateC" required value="<?= $contrat_a_modifier['DateC'] ?>"><br>
            <label>Appartement:</label>
            <input type="text" value="Appartement <?= $contrat_a_modifier['numA'] ?>" disabled>
            <input type="hidden" name="numA" value="<?= $contrat_a_modifier['numA'] ?>"><br>
            <label>Locataire:</label>
            <select name="numC_1" required>
                <option value="">-- Sélectionnez un locataire --</option>
                <?php foreach ($locataires as $locataire): ?>
                    <option value="<?= $locataire['numC'] ?>" <?= ($locataire['numC'] == $contrat_a_modifier['numC_1']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($locataire['Prenom'] . " " . $locataire['Nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit">Modifier le contrat</button>
            <a href="gestion.php">Annuler</a>
        </form>
    <?php else: ?>
        <h2>Ajouter un nouveau contrat</h2>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <label>Date du contrat:</label>
            <input type="date" name="dateC" required value="<?= date('Y-m-d') ?>"><br>
            <label>Appartement:</label>
            <select name="numA" required>
                <option value="">-- Sélectionnez un appartement --</option>
                <?php foreach ($tous_appartements as $app): ?>
                    <?php if (!isAppartementUnderContrat($bdd, $app['numA'])): ?>
                        <option value="<?= $app['numA'] ?>">Appartement <?= $app['numA'] ?> (Disponible)</option>
                    <?php else: ?>
                        <option value="<?= $app['numA'] ?>" disabled style="color:red;">Appartement <?= $app['numA'] ?> (Déjà sous contrat)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select><br>
            <label>Locataire:</label>
            <select name="numC_1" required>
                <option value="">-- Sélectionnez un locataire --</option>
                <?php foreach ($locataires as $locataire): ?>
                    <option value="<?= $locataire['numC'] ?>"><?= htmlspecialchars($locataire['Prenom'] . " " . $locataire['Nom']) ?></option>
                <?php endforeach; ?>
            </select><br>
            <button type="submit">Ajouter le contrat</button>
        </form>
    <?php endif; ?>

    <h2>Liste des appartements et leur statut</h2>
    <table>
        <tr>
            <th>Appartement</th>
            <th>Statut</th>
            <th>Contrat #</th>
            <th>Locataire</th>
            <th>Date du contrat</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($tous_appartements as $appartement):
            $contrat_info = getContratByAppartement($bdd, $appartement['numA']); ?>
            <tr>
                <td><?= $appartement['numA'] ?></td>
                <td><?= $contrat_info ? '<span style="color:red;">Sous contrat</span>' : '<span style="color:green;">Disponible</span>' ?></td>
                <td><?= $contrat_info['NumC'] ?? '-' ?></td>
                <td><?= $contrat_info ? htmlspecialchars($contrat_info['Prenom'] . ' ' . $contrat_info['Nom']) : '-' ?></td>
                <td><?= $contrat_info['DateC'] ?? '-' ?></td>
                <td><?= $contrat_info ? '<a href="?modifier='.$contrat_info['NumC'].'">Modifier</a>' : '-' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Supprimer un contrat existant</h2>
    <form method="post">
        <input type="hidden" name="action" value="delete">
        <label>Choisissez un contrat :</label>
        <select name="contrat" required>
            <option value="">-- Sélectionnez --</option>
            <?php foreach ($contrats as $contrat): ?>
                <option value="<?= $contrat['NumC'] ?>">
                    Contrat #<?= $contrat['NumC'] ?> - Appartement <?= $contrat['numA'] ?> -
                    <?= htmlspecialchars($contrat['Prenom'] . " " . $contrat['Nom']) ?> (<?= $contrat['DateC'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce contrat ?');">Supprimer</button>
    </form>

    <!-- === APPARTEMENTS === -->
    <h2>Appartements</h2>

    <h4>Ajouter un appartement</h4>
    <form method="post">
        <input type="hidden" name="action" value="addAppart">
        <input type="number" name="SurfaceH" placeholder="Surface habitable" required>
        <input type="number" name="SurfaceB" placeholder="Surface balcon">
        <input type="number" name="Capacite" placeholder="Capacité" required>
        <input type="number" name="DistancePiste" placeholder="Distance piste"><br>

        <label>Exposition :</label>
        <select name="numE" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($expositions as $expo): ?>
                <option value="<?= $expo['numE'] ?>"><?= htmlspecialchars($expo['Description']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Type :</label>
        <select name="numT" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['numT'] ?>"><?= htmlspecialchars($type['desciption']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Propriétaire :</label>
        <select name="IBAN" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($proprietaires as $prop): ?>
                <option value="<?= $prop['IBAN'] ?>"><?= htmlspecialchars($prop['Prenom'] . ' ' . $prop['Nom']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Ajouter Appartement</button>
    </form>
    <form>
<h4>Modifier un appartement</h4>
<form method="post">
    <input type="hidden" name="action" value="updateAppart">
    <label for="numA">Sélectionnez un appartement :</label>
    <select name="numA" id="numA" required>
        <option value="">-- Choisir --</option>
        <?php foreach ($tous_appartements as $app) : ?>
            <option value="<?= $app['numA'] ?>">Appartement <?= $app['numA'] ?></option>
        <?php endforeach; ?>
    </select><br>
    <input type="number" name="SurfaceH" placeholder="Surface habitable">
    <input type="number" name="SurfaceB" placeholder="Surface balcon">
    <input type="number" name="Capacite" placeholder="Capacité">
    <input type="number" name="DistancePiste" placeholder="Distance piste"><br>
    <label>Exposition :</label>
    <select name="numE">
        <option value="">-- Choisir --</option>
        <?php foreach ($expositions as $expo): ?>
            <option value="<?= $expo['numE'] ?>"><?= htmlspecialchars($expo['Description']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Type :</label>
    <select name="numT">
        <option value="">-- Choisir --</option>
        <?php foreach ($types as $type): ?>
            <option value="<?= $type['numT'] ?>"><?= htmlspecialchars($type['desciption']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Propriétaire :</label>
    <select name="IBAN">
        <option value="">-- Choisir --</option>
        <?php foreach ($proprietaires as $prop): ?>
            <option value="<?= $prop['IBAN'] ?>"><?= htmlspecialchars($prop['Prenom'] . ' ' . $prop['Nom']) ?></option>
        <?php endforeach; ?>
    </select><br>
    <button type="submit">Modifier Appartement</button>
</form>

<h4>Supprimer un appartement</h4>
<form method="post">
    <input type="hidden" name="action" value="delAppart">
    <label for="numA">Sélectionnez un appartement :</label>
    <select name="numA" id="numA" required>
        <option value="">-- Choisir --</option>
        <?php foreach ($tous_appartements as $app) : ?>
            <option value="<?= $app['numA'] ?>">Appartement <?= $app['numA'] ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cet appartement ?');">Supprimer Appartement</button>
</form>


</body>
</html>
