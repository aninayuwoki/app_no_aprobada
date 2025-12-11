<?php
// START OF PHP LOGIC
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- DATABASE CONFIGURATION ---
$servername = "127.0.0.1";
$username = "root"; // Default XAMPP username
$password = "";      // Default XAMPP password is empty
$dbname = "sis_certifi_cpateec";
$charset = "utf8mb4";

// --- DATABASE CONNECTION ---
try {
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If connection fails, stop everything and show a clear error
    die("<h1>Error Crítico de Conexión a la Base de Datos</h1><p>Mensaje: " . $e->getMessage() . "</p><p>Asegúrate de que XAMPP y el servicio MySQL estén corriendo, y que las credenciales en este archivo sean correctas.</p>");
}

// --- ADMIN CREDENTIALS ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$2y$10$Zp5iuu.KXvs/CWKE/iRp6.p0zVtafYa/yb0LQ3Ca7IC9QKz0jZ/Pe'); // Hash for "password"

// --- ROUTING LOGIC ---
$page = $_GET['page'] ?? 'public';
$is_admin_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

// Handle POST requests for login, user creation, etc.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        if ($_POST['username'] === ADMIN_USER && password_verify($_POST['password'], ADMIN_PASS_HASH)) {
            $_SESSION['loggedin'] = true;
            header('Location: index.php?page=admin_dashboard');
            exit;
        } else {
            header('Location: index.php?page=admin_login&error=1');
            exit;
        }
    }

    if ($action === 'add_user' && $is_admin_logged_in) {
        try {
            $sql = "INSERT INTO usuario (US_CEDULA, US_NOMBRE, US_APELLIDO, US_EMAIL, US_TELEFONO, US_ESTADO) VALUES (?, ?, ?, ?, ?, 'A')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$_POST['cedula'], $_POST['nombre'], $_POST['apellido'], $_POST['email'] ?? null, $_POST['telefono'] ?? null]);
            header('Location: index.php?page=admin_dashboard&success=user_added');
        } catch (PDOException $e) {
            header('Location: index.php?page=admin_dashboard&error=db_error');
        }
        exit;
    }

    if ($action === 'assign_cert' && $is_admin_logged_in) {
        try {
            $sql = "INSERT INTO inscripcion_oec (US_ID, CON_ID, IO_FECHA_CADUCIDAD, IO_ESTADO) VALUES (?, ?, ?, 'A')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$_POST['user_id'], $_POST['cert_id'], $_POST['expiry_date']]);
            header('Location: index.php?page=admin_dashboard&success=cert_assigned');
        } catch (PDOException $e) {
            header('Location: index.php?page=admin_dashboard&error=db_error');
        }
        exit;
    }
}

// --- PAGE RENDERING FUNCTIONS ---

function render_public_lookup() {
    echo <<<HTML
    <div class="container">
        <h1>Consultar Certificaciones</h1>
        <form action="index.php?page=public_results" method="post">
            <label for="cedula">Número de Cédula:</label>
            <input type="text" id="cedula" name="cedula" required>
            <button type="submit">Buscar</button>
        </form>
        <div class="nav-link"><a href="index.php?page=admin_login">Ir al panel de Administrador</a></div>
    </div>
HTML;
}

function render_public_results($conn) {
    $cedula = $_POST['cedula'] ?? '';
    if (empty($cedula)) {
        header('Location: index.php?page=public');
        exit;
    }

    echo '<div class="container">';
    echo '<h1>Resultados de la Búsqueda</h1>';

    try {
        $sql = "SELECT u.US_NOMBRE, u.US_APELLIDO, co.CON_NOMBRE, io.IO_FECHA_CADUCIDAD
                FROM usuario u
                JOIN inscripcion_oec io ON u.US_ID = io.US_ID
                JOIN certificaciones_oec co ON io.CON_ID = co.CON_ID
                WHERE u.US_CEDULA = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cedula]);
        $results = $stmt->fetchAll();

        if (count($results) > 0) {
            $user = $results[0];
            echo "<h2>Certificaciones de: " . htmlspecialchars($user['US_NOMBRE'] . ' ' . $user['US_APELLIDO']) . "</h2>";
            echo "<table><tr><th>Certificación</th><th>Estado</th><th>Fecha de Caducidad</th></tr>";
            foreach ($results as $row) {
                $status = 'Vigente';
                $class = 'vigente';
                if ($row['IO_FECHA_CADUCIDAD']) {
                    $expiry_date = new DateTime($row['IO_FECHA_CADUCIDAD']);
                    if (new DateTime() > $expiry_date) {
                        $status = 'Caducado';
                        $class = 'caducado';
                    }
                }
                echo "<tr><td>" . htmlspecialchars($row['CON_NOMBRE']) . "</td><td class='$class'>$status</td><td>" . htmlspecialchars($row['IO_FECHA_CADUCIDAD']) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No se encontraron certificaciones para la cédula proporcionada.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error-msg'>Error al realizar la consulta.</p>";
    }
    echo '<div class="nav-link"><a href="index.php?page=public">Realizar otra consulta</a></div>';
    echo '</div>';
}

function render_admin_login() {
    echo <<<HTML
    <div class="container">
        <h2>Admin Login</h2>
        <form action="index.php" method="post">
            <input type="hidden" name="action" value="login">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
HTML;
    if (isset($_GET['error'])) {
        echo '<p class="error-msg">Credenciales incorrectas.</p>';
    }
    echo '<div class="nav-link"><a href="index.php?page=public">Volver a la página pública</a></div></div>';
}

function render_admin_dashboard($conn) {
    // Fetch data for dropdowns
    $users = $conn->query("SELECT US_ID, US_NOMBRE, US_APELLIDO FROM usuario ORDER BY US_APELLIDO, US_NOMBRE")->fetchAll();
    $certs = $conn->query("SELECT CON_ID, CON_NOMBRE FROM certificaciones_oec WHERE CON_ESTADO = 'A' ORDER BY CON_NOMBRE")->fetchAll();

    echo '<div class="container admin-dashboard">';
    echo '<div class="header"><a href="index.php?page=logout">Logout</a><h1>Admin Dashboard</h1></div>';

    // Feedback messages
    if (isset($_GET['success'])) echo '<p class="feedback success">¡Operación exitosa!</p>';
    if (isset($_GET['error'])) echo '<p class="feedback error">Ocurrió un error.</p>';

    // Add User Form
    echo '<div class="form-section"><h2>Registrar Nuevo Usuario</h2><form action="index.php" method="post">
        <input type="hidden" name="action" value="add_user">
        <label>Cédula: <input type="text" name="cedula" required></label>
        <label>Nombre: <input type="text" name="nombre" required></label>
        <label>Apellido: <input type="text" name="apellido" required></label>
        <label>Email: <input type="email" name="email"></label>
        <label>Teléfono: <input type="text" name="telefono"></label>
        <button type="submit">Agregar Usuario</button></form></div>';

    // Assign Certification Form
    echo '<div class="form-section"><h2>Asignar Certificación</h2><form action="index.php" method="post">
        <input type="hidden" name="action" value="assign_cert">
        <label>Usuario: <select name="user_id" required><option value="">-- Seleccionar --</option>';
    foreach ($users as $user) {
        echo '<option value="' . htmlspecialchars($user['US_ID']) . '">' . htmlspecialchars($user['US_APELLIDO'] . ', ' . $user['US_NOMBRE']) . '</option>';
    }
    echo '</select></label><label>Certificación: <select name="cert_id" required><option value="">-- Seleccionar --</option>';
    foreach ($certs as $cert) {
        echo '<option value="' . htmlspecialchars($cert['CON_ID']) . '">' . htmlspecialchars($cert['CON_NOMBRE']) . '</option>';
    }
    echo '</select></label><label>Fecha de Caducidad: <input type="date" name="expiry_date" required></label>
        <button type="submit">Asignar Certificación</button></form></div>';
    echo '</div>';
}

// --- HTML DOCUMENT START ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Certificaciones</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 600px; }
        .admin-dashboard { max-width: 800px; }
        h1, h2 { color: #1c1e21; }
        input[type="text"], input[type="password"], input[type="email"], input[type="date"], select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dddfe2; border-radius: 6px; box-sizing: border-box; }
        button { background-color: #1877f2; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        button:hover { background-color: #166fe5; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        .nav-link { text-align: center; margin-top: 20px; }
        .nav-link a { color: #1877f2; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #dddfe2; text-align: left; }
        th { background-color: #f0f2f5; }
        .vigente { color: #31a24c; font-weight: bold; }
        .caducado { color: #fa383e; font-weight: bold; }
        .feedback { padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .success { background-color: #eaf6ec; color: #135c24; }
        .error-msg, .error { background-color: #fdecea; color: #7c242e; }
        .form-section { border: 1px solid #dddfe2; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
<?php
// --- RENDER THE CORRECT PAGE ---
if ($page === 'public') {
    render_public_lookup();
} elseif ($page === 'public_results') {
    render_public_results($conn);
} elseif ($page === 'admin_login') {
    render_admin_login();
} elseif ($page === 'admin_dashboard' && $is_admin_logged_in) {
    render_admin_dashboard($conn);
} elseif ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=public');
    exit;
} else {
    // Default to public or admin login depending on context
    if ($is_admin_logged_in) {
         header('Location: index.php?page=admin_dashboard');
    } else if (strpos($page, 'admin') !== false) {
         header('Location: index.php?page=admin_login');
    } else {
         header('Location: index.php?page=public');
    }
    exit;
}
?>
</body>
</html>
<?php
$conn = null; // Close the database connection
// END OF FILE
?>
