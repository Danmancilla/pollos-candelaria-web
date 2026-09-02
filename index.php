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
        $error = "¡Uy! Contraseña incorrecta para el usuario seleccionado.";
    }
}

// Conexión a MySQL si hay sesión activa
$conn = null;
if (isset($_SESSION['usuario'])) {
    $conn = @new mysqli("localhost", $_SESSION['db_user'], $_SESSION['db_pass'], "pollos_candelaria");
    if ($conn->connect_error) {
        $error = "Error al conectar con la Base de Datos: " . $conn->connect_error;
    }
}

// Procesar Registro de Venta
if ($conn && isset($_POST['registrar_venta'])) {
    $cliente = $conn->real_escape_string($_POST['cliente']);
    $total = floatval($_POST['total']);
    $caja = ($_SESSION['usuario'] === 'admin') ? 'Caja Admin' : ucfirst($_SESSION['usuario']);

    if ($total > 0 && !empty($cliente)) {
        $sql = "INSERT INTO pedidos (caja_origen, cliente, total) VALUES ('$caja', '$cliente', $total)";
        if ($conn->query($sql) === TRUE) {
            $mensaje = "🎉 ¡Venta registrada exitosamente a favor de $cliente por Bs. " . number_format($total, 2) . "!";
        } else {
            $error = "Error de permisos o ejecución: " . $conn->error;
        }
    } else {
        $error = "Por favor selecciona un plato e ingresa el nombre del cliente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollos Candelaria POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Fredoka', cursive, sans-serif;
            background-color: #fff6e5;
            color: #2b2b2b;
        }
        .border-cartoon {
            border: 3px solid #000 !important;
            box-shadow: 4px 4px 0px #000;
            border-radius: 16px;
        }
        .btn-cartoon {
            border: 3px solid #000;
            box-shadow: 3px 3px 0px #000;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.1s ease;
        }
        .btn-cartoon:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px #000;
        }
        .menu-card {
            background: #fff;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            user-select: none;
        }
        .menu-card:hover {
            transform: scale(1.03);
        }
        .menu-card.selected {
            background-color: #ffe699 !important;
            border-color: #ff4500 !important;
            box-shadow: 6px 6px 0px #ff4500 !important;
        }
        .ticket-box {
            background: #fff8dc;
            border: 2px dashed #000;
            border-radius: 12px;
            padding: 15px;
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['usuario']) || $conn->connect_error): ?>
    <!-- INICIO DE SESIÓN -->
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card border-cartoon p-4 text-center bg-warning" style="max-width: 420px; width: 100%;">
            <div class="mb-3">
                <span class="display-1">🍗</span>
                <h2 class="fw-bold mt-2 text-danger">POLLOS CANDELARIA</h2>
                <p class="fw-bold text-dark">¡Inicia sesión para atender!</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-cartoon py-2 fw-bold"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="fw-bold mb-1">Seleccionar Usuario:</label>
                    <select class="form-select border-cartoon fw-bold" name="usuario" required>
                        <option value="caja1">🥤 Caja 1</option>
                        <option value="caja2">🍟 Caja 2</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                </div>

                <div class="mb-4 text-start">
                    <label class="fw-bold mb-1">Contraseña:</label>
                    <input type="password" class="form-control border-cartoon fw-bold" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" name="login" class="btn btn-danger btn-cartoon w-100 py-2 fs-5">
                    ¡ENTRAR AL SISTEMA! 🚀
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- PANEL PRINCIPAL POS -->
    <nav class="navbar bg-danger border-bottom border-cartoon sticky-top py-2 mb-4">
        <div class="container">
            <span class="navbar-brand fw-bold text-white fs-3">
                🍗 POLLOS CANDELARIA POS
            </span>
            <div class="d-flex align-items-center">
                <span class="badge bg-warning text-dark border-cartoon fs-6 me-3 p-2">
                    👤 SESIÓN: <?= strtoupper($_SESSION['usuario']) ?>
                </span>
                <a href="index.php?logout=1" class="btn btn-light btn-cartoon btn-sm text-danger">
                    Cerrar Sesión 🏃
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($error): ?>
            <div class="alert alert-danger border-cartoon fw-bold mb-4"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($mensaje): ?>
            <div class="alert alert-success border-cartoon fw-bold text-center mb-4 fs-5"><?= $mensaje ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- COLUMNA IZQUIERDA: MENÚ VISUAL -->
            <div class="col-lg-7">
                <div class="card border-cartoon p-4 bg-white">
                    <h4 class="fw-bold text-danger mb-3">
                        📋 1. Selecciona el Plato del Menú
                    </h4>
                    
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <?php
                        $fotos = [
                            'Pollo Entero + Papas' => 'https://cdn-icons-png.flaticon.com/512/3075/3075977.png',
                            'Medio Pollo + Papas' => 'https://cdn-icons-png.flaticon.com/512/1046/1046784.png',
                            'Cuarto de Pollo' => 'https://cdn-icons-png.flaticon.com/512/1046/1046784.png',
                            'Gaseosa 2L' => 'https://cdn-icons-png.flaticon.com/512/2405/2405479.png',
                            'Porcion de Papas Extra' => 'https://cdn-icons-png.flaticon.com/512/1046/1046786.png',
                            'default' => 'https://cdn-icons-png.flaticon.com/512/3170/3170733.png'
                        ];

                        $productos = $conn->query("SELECT * FROM productos");
                        if ($productos && $productos->num_rows > 0):
                            while ($prod = $productos->fetch_assoc()):
                                $img = $fotos[$prod['nombre']] ?? $fotos['default'];
                        ?>
                            <div class="col">
                                <div class="card border-cartoon menu-card p-3 text-center" 
                                     data-nombre="<?= htmlspecialchars($prod['nombre'], ENT_QUOTES) ?>" 
                                     data-precio="<?= $prod['precio'] ?>">
                                    <img src="<?= $img ?>" style="height: 90px; object-fit: contain;" class="mx-auto mb-2 pointer-events-none" alt="Plato">
                                    <h5 class="fw-bold m-0 text-dark"><?= htmlspecialchars($prod['nombre']) ?></h5>
                                    <span class="badge bg-danger fs-6 border-cartoon mt-2">Bs. <?= number_format($prod['precio'], 2) ?></span>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: REGISTRO Y TICKET -->
            <div class="col-lg-5">
                <div class="card border-cartoon p-4 bg-warning">
                    <h4 class="fw-bold text-dark mb-3">
                        🛒 2. Detalle del Pedido
                    </h4>

                    <form method="POST" action="">
                        <input type="hidden" name="total" id="total_input" value="0">
                        
                        <div class="mb-3">
                            <label class="fw-bold">Nombre del Cliente:</label>
                            <input type="text" name="cliente" id="cliente_input" class="form-control border-cartoon fw-bold" placeholder="Ej. Pedro Gómez" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Cantidad:</label>
                            <input type="number" id="cantidad_input" class="form-control border-cartoon fw-bold" value="1" min="1" required>
                        </div>

                        <!-- TICKET EN VIVO -->
                        <div class="ticket-box mb-3 text-dark">
                            <h5 class="fw-bold text-center border-bottom border-dark pb-2 mb-2">🧾 TICKET DE COMPRA</h5>
                            <p class="m-0"><b>Cliente:</b> <span id="ticket_cliente" class="text-primary">---</span></p>
                            <p class="m-0"><b>Plato:</b> <span id="ticket_plato" class="text-danger">Selecciona un plato</span></p>
                            <p class="m-0"><b>Precio Unitario:</b> <span id="ticket_precio">Bs. 0.00</span></p>
                            <p class="m-0"><b>Cantidad:</b> <span id="ticket_cantidad">1</span></p>
                            <hr class="my-2 border-dark">
                            <h4 class="fw-bold text-end m-0">TOTAL: <span id="ticket_total" class="text-success">Bs. 0.00</span></h4>
                        </div>

                        <button type="submit" name="registrar_venta" id="btn_confirmar" class="btn btn-success btn-cartoon w-100 py-3 fs-4" disabled>
                            ¡CONFIRMAR PEDIDO! 🔥
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MÓDULO ADMINISTRADOR -->
        <?php if ($_SESSION['usuario'] === 'admin'): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-cartoon p-4 bg-white">
                        <h4 class="fw-bold text-danger mb-3">📊 Reporte General de Ventas (Vista Administrador)</h4>
                        <?php
                        $resumen = $conn->query("SELECT * FROM resumen_ventas_admin");
                        if ($resumen && $resumen->num_rows > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle text-center border-cartoon">
                                    <thead class="table-warning border-cartoon">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Caja de Origen</th>
                                            <th>Total Pedidos</th>
                                            <th>Ingresos Totales</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-bold">
                                        <?php while ($row = $resumen->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $row['fecha'] ?></td>
                                                <td><span class="badge bg-danger border-cartoon fs-6"><?= $row['caja_origen'] ?></span></td>
                                                <td><?= $row['total_pedidos'] ?> pedido(s)</td>
                                                <td class="text-success fs-5">Bs. <?= number_format($row['ingresos_totales'], 2) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="fw-bold text-muted m-0">No se encontraron registros de ventas acumuladas en la vista `resumen_ventas_admin`.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- HISTORIAL DE PEDIDOS REGISTRADOS -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-cartoon p-4 bg-white">
                    <h4 class="fw-bold text-dark mb-3">🕒 Últimos Pedidos Registrados</h4>
                    <?php
                    $pedidos = $conn->query("SELECT * FROM pedidos ORDER BY id DESC LIMIT 5");
                    if ($pedidos && $pedidos->num_rows > 0):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle border-cartoon">
                                <thead class="table-dark">
                                    <tr>
                                        <th># Pedido</th>
                                        <th>Caja Origen</th>
                                        <th>Cliente</th>
                                        <th>Total Pagado</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-bold">
                                    <?php while ($ped = $pedidos->fetch_assoc()): ?>
                                        <tr>
                                            <td><span class="badge bg-warning text-dark border-cartoon">#<?= $ped['id'] ?></span></td>
                                            <td><?= $ped['caja_origen'] ?></td>
                                            <td class="text-start ps-4"><?= htmlspecialchars($ped['cliente']) ?></td>
                                            <td class="text-success fs-6">Bs. <?= number_format($ped['total'], 2) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="fw-bold text-muted m-0">No hay pedidos registrados en la tabla `pedidos` aún.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let productoSeleccionado = null;
    const clienteInput = document.getElementById('cliente_input');
    const cantidadInput = document.getElementById('cantidad_input');
    const totalInput = document.getElementById('total_input');
    const btnConfirmar = document.getElementById('btn_confirmar');

    document.querySelectorAll('.menu-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.menu-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            productoSeleccionado = {
                nombre: this.dataset.nombre,
                precio: parseFloat(this.dataset.precio)
            };

            actualizarTicket();
        });
    });

    if (clienteInput && cantidadInput) {
        clienteInput.addEventListener('input', actualizarTicket);
        cantidadInput.addEventListener('input', actualizarTicket);
    }

    function actualizarTicket() {
        const cliente = clienteInput ? clienteInput.value.trim() : '';
        const cantidad = cantidadInput ? (parseInt(cantidadInput.value) || 1) : 1;

        if (cliente !== "") {
            document.getElementById('ticket_cliente').innerText = cliente;
        } else {
            document.getElementById('ticket_cliente').innerText = "---";
        }

        if (productoSeleccionado !== null) {
            document.getElementById('ticket_plato').innerText = productoSeleccionado.nombre;
            document.getElementById('ticket_precio').innerText = "Bs. " + productoSeleccionado.precio.toFixed(2);
            document.getElementById('ticket_cantidad').innerText = cantidad;

            const total = productoSeleccionado.precio * cantidad;
            document.getElementById('ticket_total').innerText = "Bs. " + total.toFixed(2);
            totalInput.value = total;
        }

        if (cliente !== "" && productoSeleccionado !== null && cantidad > 0) {
            btnConfirmar.disabled = false;
        } else {
            btnConfirmar.disabled = true;
        }
    }
});
</script>

</body>
</html>