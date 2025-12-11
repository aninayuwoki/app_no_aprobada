<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Certificaciones</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Resultados de la Búsqueda</h1>
        <?php
        include '../includes/db_connection.php';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $cedula = $_POST['cedula'];

            $stmt = $conn->prepare("
                SELECT u.US_NOMBRE, u.US_APELLIDO, co.CON_NOMBRE, io.IO_FECHA_CADUCIDAD
                FROM usuario u
                JOIN inscripcion_oec io ON u.US_ID = io.US_ID
                JOIN certificaciones_oec co ON io.CON_ID = co.CON_ID
                WHERE u.US_CEDULA = ?
            ");
            $stmt->bind_param("s", $cedula);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "<h2>Certificaciones de: " . htmlspecialchars($user['US_NOMBRE']) . " " . htmlspecialchars($user['US_APELLIDO']) . "</h2>";
                $result->data_seek(0); // Reset pointer

                echo "<table>";
                echo "<tr><th>Certificación</th><th>Estado</th><th>Fecha de Caducidad</th></tr>";

                while($row = $result->fetch_assoc()) {
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
                echo "<p>No se encontraron certificaciones para la cédula proporcionada.</p>";
            }

            $stmt->close();
        }
        $conn->close();
        ?>
        <a href="index.html">Volver a la consulta</a>
    </div>
</body>
</html>