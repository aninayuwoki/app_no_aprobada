<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /admin/');
    exit;
}

include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $cert_id = $_POST['cert_id'];
    $expiry_date = $_POST['expiry_date'];

    if (empty($user_id) || empty($cert_id) || empty($expiry_date)) {
        header("Location: /admin/dashboard.php?error=missing_fields");
        exit;
    }

    try {
        $sql = "INSERT INTO inscripcion_oec (US_ID, CON_ID, IO_FECHA_CADUCIDAD, IO_ESTADO) VALUES (?, ?, ?, 'A')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id, $cert_id, $expiry_date]);

        header("Location: /admin/dashboard.php?success=cert_assigned");
    } catch (PDOException $e) {
        // In a real app, you would log this error
        header("Location: /admin/dashboard.php?error=cert_assign_failed");
    }
    $conn = null;
}
?>