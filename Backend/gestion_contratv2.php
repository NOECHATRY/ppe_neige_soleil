<?php
require_once('BDD/connect_bdd.php');

/**
 * Ajouter un contrat
 */
function addContrat($bdd, $dateC, $numA, $numC_1) {
    try {
        // Vérifier si l'appartement est déjà sous contrat
        $stmt = $bdd->prepare("SELECT COUNT(*) as count FROM contratdelocation WHERE numA = :numA");
        $stmt->execute(['numA' => $numA]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            echo "Appartement déjà sous contrat.";
            return false; // Appartement déjà sous contrat
        }

        // Trouver le prochain numéro de contrat disponible
        $stmt = $bdd->query("SELECT MAX(NumC) as max_num FROM contratdelocation");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = $result['max_num'] + 1;

        // Insérer le contrat
        $stmt = $bdd->prepare("
            INSERT INTO contratdelocation (NumC, DateC, numA, numC_1) 
            VALUES (:numC, :dateC, :numA, :numC_1)
        ");
        return $stmt->execute([
            'numC' => $nextNum,
            'dateC' => $dateC,
            'numA' => $numA,
            'numC_1' => $numC_1
        ]);
    } catch (PDOException $e) {
        echo "il y a une erreur";
        return false;
    }
}

/**
 * Mettre à jour un contrat
 */
function updateContrat($bdd, $numC, $dateC, $numA, $numC_1) {
    try {
        $stmt = $bdd->prepare("
            UPDATE contratdelocation 
            SET DateC = :dateC, numA = :numA, numC_1 = :numC_1 
            WHERE NumC = :numC
        ");
        return $stmt->execute([
            'dateC' => $dateC,
            'numA' => $numA,
            'numC_1' => $numC_1,
            'numC' => $numC
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprimer un contrat
 */
function deleteContrat($bdd, $numC) {
    try {
        // Supprimer dans appartenir (clé étrangère)
        $stmt = $bdd->prepare("DELETE FROM appartenir WHERE NumC = :numC");
        $stmt->execute(['numC' => $numC]);

        // Supprimer le contrat
        $stmt = $bdd->prepare("DELETE FROM contratdelocation WHERE NumC = :numC");
        return $stmt->execute(['numC' => $numC]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupérer tous les contrats
 */
function getAllContrats($bdd) {
    $sql = "SELECT c.NumC, c.DateC, a.numA, cl.Nom, cl.Prenom
            FROM contratdelocation c
            JOIN appartement a ON c.numA = a.numA
            JOIN client cl ON c.numC_1 = cl.numC
            ORDER BY c.DateC DESC";
    $stmt = $bdd->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer un contrat par ID
 */
function getContratById($bdd, $numC) {
    $stmt = $bdd->prepare("
        SELECT c.NumC, c.DateC, c.numA, c.numC_1, cl.Nom, cl.Prenom 
        FROM contratdelocation c 
        JOIN client cl ON c.numC_1 = cl.numC 
        WHERE c.NumC = :numC
    ");
    $stmt->execute(['numC' => $numC]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupérer les appartements disponibles (pas encore sous contrat)
 */
function getAppartementsDisponibles($bdd) {
    $sql = "SELECT a.numA 
            FROM appartement a 
            WHERE a.numA NOT IN (SELECT numA FROM contratdelocation)
            ORDER BY a.numA";
    $stmt = $bdd->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupérer tous les locataires
 */
function getAllLocataires($bdd) {
    $sql = "SELECT c.numC, c.Nom, c.Prenom 
            FROM client c
            ORDER BY c.Nom, c.Prenom";
    $stmt = $bdd->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
