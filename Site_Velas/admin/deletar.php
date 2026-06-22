<?php
session_start();

$root = dirname(__DIR__); 
require_once $root . '/db.php';

if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id > 0 && isset($pdo)) {
        $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
        $stmt->execute([$id]);
    }
}

header('Location: dashboard.php');
exit;
?>