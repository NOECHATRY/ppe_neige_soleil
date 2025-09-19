<?php

function delAppart($bdd, $num_Appartement) {
    $query = "DELETE FROM `appartement` WHERE `numA` = :numA";
    $stmt = $bdd->prepare($query);
    return $stmt->execute([':numA' => $num_Appartement]);
}

function addAppart($bdd, $SurfaceH, $SurfaceB, $Capacite, $DistancePiste, $numE, $numT, $IBAN) {
    $query = "INSERT INTO `appartement`(`SurfaceH`, `SurfaceB`, `Capacite`, `DistancePiste`, `numE`, `numT`, `IBAN`) 
              VALUES (:SurfaceH, :SurfaceB, :Capacite, :DistancePiste, :numE, :numT, :IBAN)";
    $stmt = $bdd->prepare($query);
    return $stmt->execute([
        ':SurfaceH' => $SurfaceH,
        ':SurfaceB' => $SurfaceB,
        ':Capacite' => $Capacite,
        ':DistancePiste' => $DistancePiste,
        ':numE' => $numE,
        ':numT' => $numT,
        ':IBAN' => $IBAN
    ]);
}

function updateAppart($bdd, $num_Appartement, $SurfaceH, $SurfaceB, $Capacite, $DistancePiste, $numE, $numT, $IBAN) {
    $query = "UPDATE `appartement` SET 
                `SurfaceH` = :SurfaceH,
                `SurfaceB` = :SurfaceB,
                `Capacite` = :Capacite,
                `DistancePiste` = :DistancePiste,
                `numE` = :numE,
                `numT` = :numT,
                `IBAN` = :IBAN
              WHERE `numA` = :numA";
    $stmt = $bdd->prepare($query);
    return $stmt->execute([
        ':SurfaceH' => $SurfaceH,
        ':SurfaceB' => $SurfaceB,
        ':Capacite' => $Capacite,
        ':DistancePiste' => $DistancePiste,
        ':numE' => $numE,
        ':numT' => $numT,
        ':IBAN' => $IBAN,
        ':numA' => $num_Appartement
    ]);
}
