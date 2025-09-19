<?php
require_once "BDD/connect_bdd.php";
$bdd = bdd();
if ($bdd) {
    echo "Connexion OK !";
} else {
    echo "Connexion échouée !";
}