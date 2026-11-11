<?php
// logout.php - Déconnexion de l'utilisateur
session_start();
session_destroy();
header('Location: index.php');
exit;
