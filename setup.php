<?php
header('Content-Type: text/html; charset=utf-8');

// --- Configuración ---
$db_file = __DIR__ . '/includes/db_connection.php';
$db_example_file = __DIR__ . '/includes/db_connection.php.example';
$config_file = __DIR__ . '/includes/config.php';
$config_example_file = __DIR__ . '/includes/config.php.example';
$db_name = 'sis_certifi_cpateec';

// --- Estilos CSS ---
echo <<<HTML
<style>
    body { font-family: sans-serif; background-color: #f4f4f9; color: #333; line-height: 1.6; padding: 20px; }
    .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h1, h2 { color: #0056b3; }
    .status { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
    .ok { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    code { background: #eee; padding: 2px 5px; border-radius: 3px; }
    ul { padding-left: 20px; }
    li { margin-bottom: 10px; }
</style>
HTML;

echo '<div class="container">';
echo '<h1>Verificación del Entorno del Proyecto</h1>';

$all_ok = true;

// --- Verificación 1: Extensión PDO de PHP ---
echo '<h2>Paso 1: Revisando la configuración de PHP</h2>';
if (extension_loaded('pdo_mysql')) {
    echo '<p class="status ok">¡Excelente! La extensión <code>pdo_mysql</code> está instalada y activada.</p>';
} else {
    $all_ok = false;
    echo '<p class="status error"><strong>Error Crítico:</strong> La extensión <code>pdo_mysql</code> no está activada en tu instalación de PHP.</p>';
    echo '<div class="info">';
    echo '<strong>Solución:</strong><br>';
    echo '1. Busca el archivo <code>php.ini</code> en tu computadora (generalmente en la carpeta de instalación de PHP).<br>';
    echo '2. Ábrelo con un editor de texto.<br>';
    echo '3. Busca la línea <code>;extension=pdo_mysql</code> y quítale el punto y coma (<code>;</code>) del inicio.<br>';
    echo '4. Guarda el archivo y reinicia tu servidor web o la terminal donde ejecutas el comando <code>php -S</code>.';
    echo '</div>';
}

// --- Verificación 2: Archivos de Configuración ---
echo '<h2>Paso 2: Revisando los archivos de configuración</h2>';
if (file_exists($db_file) && file_exists($config_file)) {
    echo '<p class="status ok">¡Bien! Los archivos <code>db_connection.php</code> y <code>config.php</code> existen.</p>';
} else {
    $all_ok = false;
    echo '<p class="status error"><strong>Error:</strong> Falta uno o ambos archivos de configuración.</p>';
    echo '<div class="info">';
    echo '<strong>Solución:</strong><br>';
    echo '1. Ve a la carpeta <code>includes/</code>.<br>';
    echo '2. Copia <code>db_connection.php.example</code> y renómbralo a <code>db_connection.php</code>.<br>';
    echo '3. Copia <code>config.php.example</code> y renómbralo a <code>config.php</code>.<br>';
    echo '4. Asegúrate de editar <code>db_connection.php</code> con tu usuario y contraseña de la base de datos.';
    echo '</div>';
}

// --- Verificación 3: Conexión a la Base de Datos ---
echo '<h2>Paso 3: Probando la conexión a la base de datos</h2>';
if ($all_ok) {
    try {
        include $db_file;
        if ($conn) {
            echo '<p class="status ok">¡Conexión exitosa a la base de datos!</p>';

            // --- Verificación 4: Existencia de la base de datos y tablas ---
            $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
            if ($stmt->fetch()) {
                echo '<p class="status ok">La base de datos <code>' . $db_name . '</code> existe.</p>';

                $tables = ['usuario', 'certificaciones_oec', 'inscripcion_oec'];
                $tables_ok = true;
                foreach ($tables as $table) {
                    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                    if (!$stmt->fetch()) {
                        $tables_ok = false;
                        $all_ok = false;
                        echo "<p class='status error'>La tabla <code>$table</code> no se encontró en la base de datos.</p>";
                    }
                }
                if($tables_ok) {
                     echo '<p class="status ok">Todas las tablas necesarias existen.</p>';
                } else {
                    echo '<div class="info"><strong>Solución:</strong> Asegúrate de haber importado correctamente el archivo <code>BDPARCIAL2.sql</code>.</div>';
                }

            } else {
                $all_ok = false;
                echo '<p class="status error">La base de datos <code>' . $db_name . '</code> no existe.</p>';
                echo '<div class="info"><strong>Solución:</strong> Crea la base de datos e importa el archivo <code>BDPARCIAL2.sql</code>.</div>';
            }
        }
    } catch (PDOException $e) {
        $all_ok = false;
        echo '<p class="status error"><strong>Error de Conexión:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<div class="info">';
        echo '<strong>Solución:</strong><br>';
        echo '1. Asegúrate de que tu servidor de base de datos (MySQL/MariaDB) esté corriendo.<br>';
        echo '2. Revisa que el usuario y la contraseña en <code>includes/db_connection.php</code> sean correctos.<br>';
        echo '3. Verifica que hayas creado el usuario <code>app_user</code> con los permisos adecuados, como se indicó en las instrucciones.';
        echo '</div>';
    }
} else {
    echo '<p class="status info">La prueba de conexión se omitió porque faltan configuraciones previas.</p>';
}

// --- Resumen Final ---
echo '<h2>Resumen</h2>';
if ($all_ok) {
    echo '<p class="status ok">¡Todo parece estar configurado correctamente! El proyecto debería funcionar.</p>';
    echo '<ul>';
    echo '<li><a href="/public/" target="_blank">Ir a la página de consulta pública</a></li>';
    echo '<li><a href="/admin/" target="_blank">Ir al panel de administrador</a> (Usuario: <code>admin</code>, Contraseña: <code>password</code>)</li>';
    echo '</ul>';
} else {
    echo '<p class="status error">Se encontraron problemas en la configuración. Por favor, revisa los mensajes de error de arriba y sigue las soluciones propuestas.</p>';
}

echo '</div>';
?>