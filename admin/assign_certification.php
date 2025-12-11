<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $cert_id = $_POST['cert_id'];
    $expiry_date = $_POST['expiry_date'];

    // Quick validation
    if (!empty($user_id) && !empty($cert_id) && !empty($expiry_date)) {

        $stmt = $conn->prepare("INSERT INTO inscripcion_oec (US_ID, CON_ID, IO_FECHA_CADUCIDAD, IO_ESTADO) VALUES (?, ?, ?, 'A')");
        $stmt->bind_param("iis", $user_id, $cert_id, $expiry_date);

        if ($stmt->execute()) {
            header("Location: dashboard.php?success=cert_assigned");
        } else {
            header("Location: dashboard.php?error=cert_assign_failed");
        }
        $stmt->close();
    } else {
        header("Location: dashboard.php?error=missing_fields");
    }
    $conn->close();
}
?>