<?php
require_once 'auth/session.php';

if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['rol'])) {
    header('Location: portals/portal_' . $_SESSION['rol'] . '.php');
    exit;
}

include 'index.html';
