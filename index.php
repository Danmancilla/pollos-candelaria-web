<?php
session_start();

// Manejo de Cierre de Sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$error = "";
$mensaje = "";

// Proceso de Login
if (isset($_POST['login'])) {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // Credenciales de la BD según el rol seleccionado
    $db_user = "";
    $db_pass = "";

    if ($user === "caja1") {
        $db_user = "caja1";
        $db_pass = "Caja1Pass123!";
    } elseif ($user === "caja2") {
        $db_user = "caja2";
        $db_pass = "Caja2Pass123!";
    } elseif ($user === "admin") {
        $db_user = "admin_candelaria";
        $db_pass = "AdminPass123!";
    }

    if ($pass === $db_pass && $db_user !== "") {
        $_SESSION['usuario'] = $user;
        $_SESSION['db_user'] = $db_user;
        $_SESSION['db_pass'] = $db_pass;
    } else {
        $error = "Contraseña incorrecta para el usuario seleccionado.";
    }
}

// Conexión a MySQL si hay sesión activa
$conn = null;
if (isset($_SESSION['usuario'])) {
    $conn = new mysqli("localhost", $_SESSION['db_user'], $_SESSION['db_pass'], "pollos_candelaria");
    if ($conn->connect_error) {
        $error = "Error al conectar con la Base de Datos: " . $conn->connect_error;
    }
}

// Procesar Registro de Venta (Cajas y Admin)
if ($conn && isset($_POST['registrar_venta'])) {
    $cliente = $conn->real_escape_string($_POST['cliente']);
    $total = floatval($_POST['total']);
    $caja = ($_SESSION['usuario'] === 'admin') ? 'Caja Admin' : ucfirst($_SESSION['usuario']);

    $sql = "INSERT INTO pedidos (caja_origen, cliente, total) VALUES ('$caja', '$cliente', $total)";
    if ($conn->query($sql) === TRUE) {
        $mensaje = "¡Venta registrada exitosamente!";
    } else {
        $error = "Error de permisos o ejecución: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollos Candelaria - Sistema POS</title>
    <style>
        :root {
            --primary: #8b0000;
            --primary-hover: #a00000;
            --accent: #ffb703;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #2b2b2b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        header {
            background-color: var(--primary);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        header h1 {
            margin: 0;
            font-size: 2.5rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        header p {
            margin: 5px 0 0 0;
            color: var(--accent);
            font-weight: 500;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error { background-color: #ffe6e6; color: #d90429; border: 1px solid #ffb3b3; }
        .alert-success { background-color: #e6ffe6; color: #2b9348; border: 1px solid #b3ffb3; }

        form label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
        }

        form input, form select {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 20px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn:hover { background-color: var(--primary-hover); }

        .user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .user-badge {
            background: #eee;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th { background-color: #f1f1f1; font-weight: 600; }
        .logout-link { color: #d90429; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <h1>🍗 Pollos Candelaria</h1>
    <p>Sistema de Gestión & Punto de Venta</p>
</header>

<div class="container">

    <?php if (!isset($_SESSION['usuario'])): ?>
        <!-- PANTALLA DE INICIO DE SESIÓN -->
        <div class="card" style="max-width: 400px; margin: 0 auto;">
            <h2 style="text-align: center; margin-top: 0;">Iniciar Sesión</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label>Seleccionar Usuario:</label>
                <select name="usuario" required>
                    <option value="caja1">Caja 1</option>
                    <option value="caja2">Caja 2</option>
                    <option value="admin">Administrador</option>
                </select>

                <label>Contraseña:</label>
                <input type="password" name="password" required placeholder="••••••••">

                <button type="submit" name="login" class="btn">Ingresar al Sistema</button>
            </form>
        </div>

    <?php else: ?>
        <!-- PANTALLA PRINCIPAL CON SESIÓN INICIADA -->
        <div class="card">
            <div class="user-bar">
                <div>
                    Sesión activa: <span class="user-badge"><?= $_SESSION['usuario'] ?></span>
                </div>
                <a href="index.php?logout=1" class="logout-link">Cerrar Sesión ➔</a>
            </div>

            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($mensaje): ?><div class="alert alert-success"><?= $mensaje ?></div><?php endif; ?>

            <!-- MÓDULO DE VENTAS (Disponible para Cajas y Admin) -->
            <h3>🛒 Registrar Nueva Venta</h3>
            <form method="POST" action="">
                <label>Nombre del Cliente:</label>
                <input type="text" name="cliente" required placeholder="Ej. Pedro Gómez">

                <label>Monto Total (Bs.):</label>
                <input type="number" step="0.01" name="total" required placeholder="Ej. 65.00">

                <button type="submit" name="registrar_venta" class="btn">Confirmar Pedido</button>
            </form>
        </div>

        <!-- MÓDULO DEL ADMINISTRADOR (Exclusivo para la cuenta 'admin') -->
        <?php if ($_SESSION['usuario'] === 'admin'): ?>
            <div class="card">
                <h3 style="color: var(--primary);">📊 Reporte General de Ventas (Solo Administrador)</h3>
                <?php
                $resumen = $conn->query("SELECT * FROM resumen_ventas_admin");
                if ($resumen && $resumen->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Caja de Origen</th>
                                <th>Total Pedidos</th>
                                <th>Ingresos (Bs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $resumen->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['fecha'] ?></td>
                                    <td><?= $row['caja_origen'] ?></td>
                                    <td><?= $row['total_pedidos'] ?></td>
                                    <td><strong><?= number_format($row['ingresos_totales'], 2) ?> Bs.</strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No se encontraron registros de ventas acumuladas.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- MÓDULO DE MENÚ / CONSULTA (Disponible para todos) -->
        <div class="card">
            <h3>📋 Menú de Productos</h3>
            <?php
            $productos = $conn->query("SELECT * FROM productos");
            if ($productos && $productos->num_rows > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prod = $productos->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($prod['nombre']) ?></td>
                                <td><strong><?= number_format($prod['precio'], 2) ?> Bs.</strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

</body>
</html>