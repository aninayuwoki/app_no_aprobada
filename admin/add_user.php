<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];

    // Basic validation
    if (!empty($cedula) && !empty($nombre) && !empty($apellido)) {

        $stmt = $conn->prepare("INSERT INTO usuario (US_CEDULA, US_NOMBRE, US_APELLIDO, US_EMAIL, US_TELEFONO, US_ESTADO) VALUES (?, ?, ?, ?, ?, 'A')");
        $stmt->bind_param("sssss", $cedula, $nombre, $apellido, $email, $telefono);

        if ($stmt->execute()) {
            header("Location: dashboard.php?success=user_added");
        } else {
            header("Location: dashboard.php?error=user_add_failed");
        }
        $stmt->close();
    } else {
        header("Location: dashboard.php?error=missing_fields");
    }
    $conn->close();
}
?>