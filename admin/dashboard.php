<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
include '../includes/db_connection.php';

// Fetch users for the dropdown
$users_result = $conn->query("SELECT US_ID, US_NOMBRE, US_APELLIDO FROM usuario ORDER BY US_APELLIDO, US_NOMBRE");

// Fetch certifications for the dropdown
$certs_result = $conn->query("SELECT CON_ID, CON_NOMBRE FROM certificaciones_oec WHERE CON_ESTADO = 'A' ORDER BY CON_NOMBRE");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <a href="logout.php">Logout</a>
        <h1>Admin Dashboard</h1>

        <?php
        if (isset($_GET['success'])) {
            $message = '';
            switch ($_GET['success']) {
                case 'user_added':
                    $message = 'Usuario agregado exitosamente.';
                    break;
                case 'cert_assigned':
                    $message = 'Certificación asignada exitosamente.';
                    break;
            }
            if ($message) echo "<div class='feedback success'>$message</div>";
        }
        if (isset($_GET['error'])) {
            $message = '';
            switch ($_GET['error']) {
                case 'user_add_failed':
                    $message = 'Error al agregar el usuario.';
                    break;
                case 'cert_assign_failed':
                    $message = 'Error al asignar la certificación.';
                    break;
                case 'missing_fields':
                    $message = 'Por favor, complete todos los campos requeridos.';
                    break;
            }
            if ($message) echo "<div class='feedback error'>$message</div>";
        }
        ?>

        <div class="form-section">
            <h2>Registrar Nuevo Usuario</h2>
            <form action="add_user.php" method="post">
                <label for="cedula">Cédula:</label>
                <input type="text" id="cedula" name="cedula" required>
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono">
                <button type="submit">Agregar Usuario</button>
            </form>
        </div>

        <div class="form-section">
            <h2>Asignar Certificación a Usuario</h2>
            <form action="assign_certification.php" method="post">
                <label for="user_id">Usuario:</label>
                <select name="user_id" id="user_id" required>
                    <option value="">-- Seleccione un Usuario --</option>
                    <?php
                    if ($users_result->num_rows > 0) {
                        while($row = $users_result->fetch_assoc()) {
                            echo "<option value='" . $row['US_ID'] . "'>" . htmlspecialchars($row['US_APELLIDO'] . ', ' . $row['US_NOMBRE']) . "</option>";
                        }
                    }
                    ?>
                </select>

                <label for="cert_id">Certificación:</label>
                <select name="cert_id" id="cert_id" required>
                    <option value="">-- Seleccione una Certificación --</option>
                    <?php
                    if ($certs_result->num_rows > 0) {
                        while($row = $certs_result->fetch_assoc()) {
                            echo "<option value='" . $row['CON_ID'] . "'>" . htmlspecialchars($row['CON_NOMBRE']) . "</option>";
                        }
                    }
                    ?>
                </select>

                <label for="expiry_date">Fecha de Caducidad:</label>
                <input type="date" name="expiry_date" required>
                <button type="submit">Asignar Certificación</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>