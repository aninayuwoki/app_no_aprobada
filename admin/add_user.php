<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'] ?? null;
    $telefono = $_POST['telefono'] ?? null;

    if (empty($cedula) || empty($nombre) || empty($apellido)) {
        header("Location: dashboard.php?error=missing_fields");
        exit;
    }

    try {
        $sql = "INSERT INTO usuario (US_CEDULA, US_NOMBRE, US_APELLIDO, US_EMAIL, US_TELEFONO, US_ESTADO) VALUES (?, ?, ?, ?, ?, 'A')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cedula, $nombre, $apellido, $email, $telefono]);

        header("Location: dashboard.php?success=user_added");
    } catch (PDOException $e) {
        header("Location: dashboard.php?error=user_add_failed");
    }
    $conn = null;
}
?>