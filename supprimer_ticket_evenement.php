<?php
session_start();
if (!isset($_SESSION['is_admin_fixed']) || $_SESSION['is_admin_fixed'] !== true) {
    header("Location: connexion.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'gestiondebillet');
$conn->set_charset("utf8mb4");

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID du ticket manquant.");
}

$stmt = $conn->prepare("DELETE FROM ticketevenement WHERE Id_TicketEvenement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: gerer_ticket.php");
exit();