<?php

require_once "gestion_locataire.php";   
require_once "gestion_appart.php";
require_once "gestion_contratv2.php";


function handleFormActions($bdd) {
    $action = $_POST['action'] ?? null;
    $message = "";

    if ($action) {
        switch ($action) {
            // === LOCATAIRE ===
            case "addLocataire":
                $ok = addloc($bdd, $_POST['Nom'], $_POST['Prenom'], $_POST['Adresse'], $_POST['Mail'], $_POST['TelF'], $_POST['TelP']);
                $message = $ok ? "Locataire ajouté" : "Erreur ajout locataire";
                break;

            case "updateloc":
                $ok = updateloc($bdd, $_POST['numC'], $_POST['Nom'], $_POST['Prenom'], $_POST['Adresse'], $_POST['Mail'], $_POST['TelF'], $_POST['TelP']);
                $message = $ok ? "Locataire modifié" : "Erreur modification";
                break;

            case "delloc":
                $ok = delloc($bdd, $_POST['numC']);
                $message = $ok ? "Locataire supprimé" : "Erreur suppression";
                break;

            // === APPARTEMENTS ===
            case "addAppart":
                $ok = addAppart($bdd, $_POST['SurfaceH'], $_POST['SurfaceB'], $_POST['Capacite'], $_POST['DistancePiste'], $_POST['numE'], $_POST['numT'], $_POST['IBAN']);
                $message = $ok ? "Appartement ajouté" : "Erreur ajout appartement";
                break;

            case "updateAppart":
                $ok = updateAppart($bdd, $_POST['numA'], $_POST['SurfaceH'], $_POST['SurfaceB'], $_POST['Capacite'], $_POST['DistancePiste'], $_POST['numE'], $_POST['numT'], $_POST['IBAN']);
                $message = $ok ? "Appartement modifié" : "Erreur modification appartement";
                break;

            case "delAppart":
                $ok = delAppart($bdd, $_POST['numA']);
                $message = $ok ? "Appartement supprimé" : "Erreur suppression appartement";
                break;

            // === CONTRATS ===
            case "addContrat":
                $ok = addContrat($bdd, $_POST['dateC'], $_POST['numA'], $_POST['numC_1']);
                $message = $ok ? "Contrat ajouté" : "Erreur ajout contrat ou appartement déjà sous contrat";
                break;

            case "updateContrat":
                if (!isset($_POST['numC'])) {
                    $message = "Numéro de contrat manquant";
                    break;
                }
                $ok = updateContrat($bdd, $_POST['numC'], $_POST['dateC'], $_POST['numA'], $_POST['numC_1']);
                $message = $ok ? "Contrat modifié" : "Erreur modification contrat";
                break;

            case "delContrat":
                $ok = deleteContrat($bdd, $_POST['contrat']);
                $message = $ok ? "Contrat supprimé" : "Erreur suppression contrat";
                break;

            default:
                $message = "Action inconnue";
            break;  

        }
    }

    return $message;
}

function fetchAllData($bdd) {
    $data = [
        'locataires' => [],
        'appartements' => [],
        'contrats' => [],
        'expositions' => [],
        'types' => [],
        'proprietaires' => []
    ];

    try {
        $stmt = $bdd->query("SELECT numC, Nom, Prenom FROM client ORDER BY Nom, Prenom");
        $data['locataires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur locataires : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    try {
        $stmt = $bdd->query("SELECT numA, SurfaceH, SurfaceB, Capacite, DistancePiste, numE, numT, IBAN FROM appartement ORDER BY numA");
        $data['appartements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur appartements : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    try {
        $stmt = $bdd->query("
            SELECT c.NumC, c.DateC, c.numA, c.numC_1, cl.Nom, cl.Prenom
            FROM contratdelocation c
            LEFT JOIN client cl ON c.numC_1 = cl.numC
            ORDER BY c.DateC DESC
        ");
        $data['contrats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur contrats : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    try {
        $stmt = $bdd->query("SELECT numE, Description FROM exposition");
        $data['expositions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur expositions : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    try {
        $stmt = $bdd->query("SELECT numT, desciption FROM type");
        $data['types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur types : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    try {
        $stmt = $bdd->query("
            SELECT p.IBAN, c.Nom, c.Prenom 
            FROM propritétaire p
            JOIN client c ON p.numC = c.numC
        ");
        $data['proprietaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "<p>Erreur propriétaires : " . htmlspecialchars($e->getMessage()) . "</p>"; }

    return $data;
}

// Fonctions utilitaires
function getContratByAppartement($bdd, $numA) {
    $stmt = $bdd->prepare("
        SELECT c.NumC, c.DateC, c.numA, c.numC_1, cl.Nom, cl.Prenom
        FROM contratdelocation c
        LEFT JOIN client cl ON c.numC_1 = cl.numC
        WHERE c.numA = :numA
        LIMIT 1
    ");
    $stmt->execute([':numA' => $numA]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isAppartementUnderContrat($bdd, $numA) {
    $stmt = $bdd->prepare("SELECT COUNT(*) FROM contratdelocation WHERE numA = :numA");
    $stmt->execute([':numA' => $numA]);
    return (int)$stmt->fetchColumn() > 0;
}
?>
