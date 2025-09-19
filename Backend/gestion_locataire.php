<?php
require_once('gestion_contratv2.php');

function delloc($bdd, $num_client){
    // 1) Supprimer les contrats où le client est locataire
    $sql = "DELETE FROM appartenir 
            WHERE NumC IN (SELECT NumC FROM contratdelocation WHERE numC_1 = :numero_c)";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);

    $sql = "DELETE FROM contratdelocation WHERE numC_1 = :numero_c";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);

    // 2) Supprimer le locataire (si présent)
    $sql = "DELETE FROM locataire WHERE numC = :numero_c";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);

    // 3) Récupérer les appartements du propriétaire
    $sql = "SELECT numA FROM appartement 
            WHERE IBAN IN (SELECT IBAN FROM propritétaire WHERE numC = :numero_c)";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);
    $appartements = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($appartements) {
        //Supprimer d'abord les contrats liés à ces appartements
        $sql = "DELETE FROM appartenir 
                WHERE NumC IN (SELECT NumC FROM contratdelocation WHERE numA = :numA)";
        $stmtAppartenir = $bdd->prepare($sql);

        $sql = "DELETE FROM contratdelocation WHERE numA = :numA";
        $stmtContrat = $bdd->prepare($sql);

        $sql = "DELETE FROM concerner WHERE numA = :numA";
        $stmtConcerner = $bdd->prepare($sql);

        $sql = "DELETE FROM appartement WHERE numA = :numA";
        $stmtAppartement = $bdd->prepare($sql);

        foreach ($appartements as $numA) {
            // supprimer contrats liés à cet appart
            $stmtAppartenir->execute([':numA' => $numA]);
            $stmtContrat->execute([':numA' => $numA]);
            // supprimer tarifs
            $stmtConcerner->execute([':numA' => $numA]);
            // supprimer appart
            $stmtAppartement->execute([':numA' => $numA]);
        }
    }

    // 4) Supprimer le propriétaire
    $sql = "DELETE FROM propritétaire WHERE numC = :numero_c";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);

    // 5) Supprimer enfin le client
    $sql = "DELETE FROM client WHERE numC = :numero_c";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([':numero_c' => $num_client]);

    return true;
}








function addloc($bdd, $nom, $prenom, $adresse, $mail, $tel_fix, $tel_portable){
    $query = "INSERT INTO `Client`(`Nom`, `Prenom`, `Adresse`, `Mail`, `TelF`, `TelP`) 
        VALUES (:nom, :prenom, :adresse, :mail, :telf, :telp)";
    $stmt = $bdd->prepare($query);
    return $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':adresse' => $adresse,
        ':mail' => $mail,
        ':telf' => $tel_fix,
        ':telp' => $tel_portable
    ]);
}


function updateloc($bdd, $num_client, $nom, $prenom, $adresse, $mail, $tel_fix, $tel_portable){
    $query = "UPDATE `Client` SET 
                `Nom` = :nom,
                `Prenom` = :prenom,
                `Adresse` = :adresse,
                `Mail` = :mail,
                `TelF` = :telf,
                `TelP` = :telp
    WHERE `numC` = :numC";
    $stmt = $bdd->prepare($query);
    return $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':adresse' => $adresse,
        ':mail' => $mail,
        ':telf' => $tel_fix,
        ':telp' => $tel_portable,
        ':numC' => $num_client
    ]);
}


function dellAllContrat_Locataire($bdd, int $numClient) {
    try {
        // Supprimer d'abord dans 'appartenir'
        $sql = "DELETE FROM appartenir 
                WHERE NumC IN (SELECT NumC FROM contratdelocation WHERE numC_1 = :numClient)";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([':numClient' => $numClient]);

        // Puis supprimer les contrats
        $sql = "DELETE FROM contratdelocation WHERE numC_1 = :numClient";
        $stmt = $bdd->prepare($sql);
        $stmt->execute([':numClient' => $numClient]);

    } catch (Exception $e) {
        error_log("Erreur suppression contrats : " . $e->getMessage());
    }
}

