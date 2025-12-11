<?php
header('Content-Type: text/html; charset=utf-8');

// --- Configuración ---
$db_file = __DIR__ . '/includes/db_connection.php';
$db_example_file = __DIR__ . '/includes/db_connection.php.example';
$config_file = __DIR__ . '/includes/config.php';
$config_example_file = __DIR__ . '/includes/config.php.example';

// --- Estilos CSS ---
echo <<<HTML
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f2f5; color: #1c1e21; line-height: 1.6; padding: 20px; }
    .container { max-width: 800px; margin: 20px auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    h1 { color: #1877f2; text-align: center; margin-bottom: 25px; }
    h2 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 25px; }
    .status { padding: 12px 15px; margin-bottom: 15px; border-radius: 6px; font-weight: 500; display: flex; align-items: center; }
    .status::before { content: ''; display: inline-block; width: 20px; height: 20px; margin-right: 10px; background-size: contain; background-repeat: no-repeat; }
    .ok { background-color: #eaf6ec; color: #135c24; border: 1px solid #c7e6d2; }
    .ok::before { content: '✓'; color: #28a745; font-weight: bold; }
    .error { background-color: #fdecea; color: #7c242e; border: 1px solid #f9d0d5; }
    .error::before { content: '✗'; color: #dc3545; font-weight: bold; }
    .info { background-color: #e5f3fe; color: #0c5460; border: 1px solid #bde5eb; font-size: 0.9em; }
    code { background: #e9ebee; padding: 3px 6px; border-radius: 4px; font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace; }
    ul { padding-left: 20px; }
    li { margin-bottom: 12px; }
    strong { color: #000; }
</style>
HTML;

echo '<div class="container">';
echo '<h1>Diagnóstico del Entorno del Proyecto</h1>';

$all_ok = true;

// --- Verificación 1: Extensión PDO de PHP ---
echo '<h2>Paso 1: Revisando la configuración de PHP</h2>';
if (extension_loaded('pdo_mysql')) {
    echo '<p class="status ok">La extensión <code>pdo_mysql</code> está instalada y activada.</p>';
} else {
    $all_ok = false;
    echo '<p class="status error"><strong>Error Crítico:</strong> La extensión <code>pdo_mysql</code> no está activada.</p>';
    echo '<div class="info"><strong>Solución:</strong> Busca tu archivo <code>php.ini</code> (en XAMPP, puedes encontrarlo desde el panel de control), y quita el punto y coma (<code>;</code>) de la línea <code>;extension=pdo_mysql</code>. Guarda y reinicia el servidor Apache.</div>';
}

// --- Verificación 2: Archivos de Configuración ---
echo '<h2>Paso 2: Revisando los archivos de configuración</h2>';
if (file_exists($db_file) && file_exists($config_file)) {
    echo '<p class="status ok">Los archivos de configuración <code>db_connection.php</code> y <code>config.php</code> existen.</p>';
} else {
    $all_ok = false;
    echo '<p class="status error"><strong>Error:</strong> Falta el archivo <code>includes/db_connection.php</code> o <code>includes/config.php</code>.</p>';
    echo '<div class="info"><strong>Solución:</strong> Asegúrate de haber renombrado los archivos <code>.example</code> a su versión sin <code>.example</code> dentro de la carpeta <code>includes</code>.</div>';
}

// --- Verificación 3: Conexión a la Base de Datos ---
echo '<h2>Paso 3: Probando la conexión a la base de datos</h2>';
if (file_exists($db_file)) {
    // Manually parse db_connection.php to show what's being used
    $db_content = file_get_contents($db_file);
    $db_user = preg_match('/\$username\s*=\s*"(.*)"/', $db_content, $matches) ? $matches[1] : '[No encontrado]';
    $db_pass_len = preg_match('/\$password\s*=\s*"(.*)"/', $db_content, $matches) ? strlen($matches[1]) : 0;
    $db_name = preg_match('/\$dbname\s*=\s*"(.*)"/', $db_content, $matches) ? $matches[1] : '[No encontrado]';

    echo "<p class='status info'>Intentando conectar a la base de datos <code>$db_name</code> con el usuario <code>$db_user</code>...</p>";

    try {
        include $db_file; // This will define $conn
        if ($conn) {
            echo '<p class="status ok">¡Conexión exitosa a la base de datos!</p>';
            $stmt = $conn->query("SHOW TABLES");
            if($stmt->rowCount() > 0){
                echo '<p class="status ok">La base de datos contiene tablas. La importación parece correcta.</p>';
            } else {
                $all_ok = false;
                echo '<p class="status error"><strong>Error:</strong> La base de datos está vacía.</p>';
                echo '<div class="info"><strong>Solución:</strong> Asegúrate de haber importado el archivo <code>BDPARCIAL2.sql</code> a la base de datos <code>' . $db_name . '</code>.</div>';
            }
        }
    } catch (PDOException $e) {
        $all_ok = false;
        echo '<p class="status error"><strong>Error de Conexión:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<div class="info">';
        echo '<strong>Posibles Soluciones:</strong><br>';
        echo '<ul>';
        echo '<li><b>Firewall:</b> Revisa que el Firewall de Windows o tu antivirus no esté bloqueando el puerto 3306. Intenta desactivarlo temporalmente para la prueba.</li>';
        echo '<li><b>Credenciales:</b> Confirma que el usuario <code>' . $db_user . '</code> y su contraseña (longitud: ' . $db_pass_len . ' caracteres) en <code>includes/db_connection.php</code> son correctos.</li>';
        echo '<li><b>Servidor de BD:</b> Asegúrate de que el servicio MySQL/MariaDB esté iniciado en XAMPP.</li>';
        echo '</ul>';
        echo '</div>';
    }
} else {
    $all_ok = false;
    echo '<p class="status info">La prueba de conexión se omitió porque el archivo <code>db_connection.php</code> no existe.</p>';
}

// --- Resumen Final ---
echo '<h2>Resumen Final</h2>';
if ($all_ok) {
    echo '<p class="status ok">¡Excelente! Tu entorno parece estar listo. La aplicación debería funcionar.</p>';
    echo '<ul>';
    echo '<li><a href="/public/" target="_blank">Probar la página de consulta pública</a></li>';
    echo '<li><a href="/admin/" target="_blank">Probar el panel de administrador</a> (Usuario: <code>admin</code>, Contraseña: <code>password</code>)</li>';
    echo '</ul>';
} else {
    echo '<p class="status error">Se han encontrado uno o más problemas críticos. Por favor, revisa los errores de arriba y aplica las soluciones sugeridas.</p>';
}

echo '</div>';
?>