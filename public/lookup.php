<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Certificaciones</title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Resultados de la Búsqueda</h1>
        <?php
        // Always display errors for debugging
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        include '../includes/db_connection.php';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $cedula = $_POST['cedula'];

            try {
                $sql = "
                    SELECT u.US_NOMBRE, u.US_APELLIDO, co.CON_NOMBRE, io.IO_FECHA_CADUCIDAD
                    FROM usuario u
                    JOIN inscripcion_oec io ON u.US_ID = io.US_ID
                    JOIN certificaciones_oec co ON io.CON_ID = co.CON_ID
                    WHERE u.US_CEDULA = ?
                ";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$cedula]);
                $results = $stmt->fetchAll();

                if (count($results) > 0) {
                    $user = $results[0];
                    echo "<h2>Certificaciones de: " . htmlspecialchars($user['US_NOMBRE']) . " " . htmlspecialchars($user['US_APELLIDO']) . "</h2>";

                    echo "<table>";
                    echo "<tr><th>Certificación</th><th>Estado</th><th>Fecha de Caducidad</th></tr>";

                    foreach($results as $row) {
                        $fecha_caducidad = $row['IO_FECHA_CADUCIDAD'];
                        $estado = '';
                        $estado_class = '';

                        if ($fecha_caducidad) {
                            $hoy = new DateTime();
                            $caducidad_dt = new DateTime($fecha_caducidad);
                            if ($caducidad_dt < $hoy) {
                                $estado = "Caducado";
                                $estado_class = "caducado";
                            } else {
                                $estado = "Vigente";
                                $estado_class = "vigente";
                            }
                        } else {
                            $estado = "Sin fecha";
                            $fecha_caducidad = "N/A";
                        }

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['CON_NOMBRE']) . "</td>";
                        echo "<td class='" . $estado_class . "'>" . $estado . "</td>";
                        echo "<td>" . $fecha_caducidad . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No se encontraron certificaciones para la cédula proporcionada. Por favor, verifique el número o contacte al administrador.</p>";
                }

            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error de base de datos: " . $e->getMessage() . "</p>";
            }
        }
        $conn = null;
        ?>
        <a href="/public/">Volver a la consulta</a>
    </div>
</body>
</html>