<?php
// Script de déconnexion/reconnexion pour l'admin
session_start();
session_destroy();
header("Location: /login");
exit;
?>