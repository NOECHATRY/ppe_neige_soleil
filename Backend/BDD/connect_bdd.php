<?php
function bdd(){
    $dsn = 'mysql:host=localhost;dbname=neige_soleil';
    $db_user = 'root';
    $db_password = '';

    try {
        $pdo = new PDO($dsn, $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo; // <--- c’est la ligne manquante
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : {$e->getMessage()}");
    }
}
?>
