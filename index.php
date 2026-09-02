<?php
session_start();

$db_user = $_SESSION['db_user'] ?? null;
$db_pass = $_SESSION['db_pass'] ?? null;
$db_host = 'localhost';
$db_name = 'pollos_candelaria';

$conn = null;
$error_login = '';

// Login
if (isset($_POST['login'])) {
    $user_input = $_POST['usuario'];
    $pass_input = $_POST['password'];

    try {
        $conn_test = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $user_input, $pass_input);
        $_SESSION['db_user'] = $user_input;
        $_SESSION['db_pass'] = $pass_input;
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $error_login = "¡Uy! Credenciales incorrectas o acceso denegado.";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Conexión
if ($db_user && $db_pass) {
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

// Registro de Venta
$mensaje_venta = '';
if ($conn && isset($_POST['registrar_venta'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];
    $cliente = trim($_POST['cliente']);

    try {
        // Consulta usando id_producto correctamente
        $stmt_prod = $conn->prepare("SELECT nombre, precio FROM productos WHERE id_producto = ?");
        $stmt_prod->execute([$id_producto]);
        $prod = $stmt_prod->fetch(PDO::FETCH_ASSOC);

        if ($prod && $cantidad > 0) {
            $total = $prod['precio'] * $cantidad;
            $nombre_cliente = $cliente ?: 'Cliente General';

            $conn->beginTransaction();
            $stmt_ped = $conn->prepare("INSERT INTO pedidos (cliente, total, usuario_registro) VALUES (?, ?, ?)");
            $stmt_ped->execute([$nombre_cliente, $total, $db_user]);
            $id_pedido = $conn->lastInsertId();

            $stmt_det = $conn->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt_det->execute([$id_pedido, $id_producto, $cantidad, $prod['precio']]);

            $conn->commit();
            $mensaje_venta = "
            <div class='alert-cartoon text-center mb-4'>
                <h4 class='fw-black text-success m-0'>🎉 ¡PEDIDO #$id_pedido REGISTRADO!</h4>
                <p class='m-0 mt-1 fs-5'><b>Cliente:</b> $nombre_cliente | <b>Plato:</b> {$prod['nombre']} (x$cantidad) | <b>Total:</b> Bs. ".number_format($total, 2)."</p>
            </div>";
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $mensaje_venta = "<div class='alert alert-danger fw-bold border-cartoon mb-4'>Error al registrar: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pollos Candelaria - POS Fun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        .alert-cartoon {
            background-color: #a8ffb2;
            border: 3px solid #000;
            box-shadow: 4px 4px 0px #000;
            border-radius: 16px;
            padding: 15px;
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

<?php if (!$conn): ?>
    <!-- LOGIN -->
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card border-cartoon p-4 text-center bg-warning" style="max-width: 420px; width: 100%;">
            <div class="mb-3">
                <span class="display-1">🍗</span>
                <h2 class="fw-bold mt-2 text-danger">POLLOS CANDELARIA</h2>
                <p class="fw-bold text-dark">¡Inicia sesión para atender!</p>
            </div>

            <?php if ($error_login): ?>
                <div class="alert alert-danger border-cartoon py-2 fw-bold"><?= $error_login ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="fw-bold mb-1">Usuario:</label>
                    <select class="form-select border-cartoon fw-bold" name="usuario" required>
                        <option value="caja1">🥤 Caja 1</option>
                        <option value="caja2">🍟 Caja 2</option>
                        <option value="admin_candelaria">👑 Administrador</option>
                    </select>
                </div>

                <div class="mb-4 text-start">
                    <label class="fw-bold mb-1">Contraseña:</label>
                    <input type="password" class="form-control border-cartoon fw-bold" name="password" placeholder="***" required>
                </div>

                <button type="submit" name="login" class="btn btn-danger btn-cartoon w-100 py-2 fs-5">
                    ¡ENTRAR A TRABAJAR! 🚀
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- PANEL PRINCIPAL -->
    <nav class="navbar bg-danger border-bottom border-cartoon sticky-top py-2 mb-4">
        <div class="container">
            <span class="navbar-brand fw-bold text-white fs-3">
                🍗 POLLOS CANDELARIA POS
            </span>
            <div class="d-flex align-items-center">
                <span class="badge bg-warning text-dark border-cartoon fs-6 me-3 p-2">
                    👤 <?= htmlspecialchars($db_user) ?>
                </span>
                <a href="?logout=1" class="btn btn-light btn-cartoon btn-sm">
                    Salir 🏃
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <?= $mensaje_venta ?>

        <div class="row g-4">
            <!-- MENÚ DE PLATOS -->
            <div class="col-lg-7">
                <div class="card border-cartoon p-4 bg-white">
                    <h4 class="fw-bold text-danger mb-3">
                        📋 1. Selecciona el Plato del Menú
                    </h4>
                    
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <?php
                        $fotos = [
                            '1' => 'https://cdn-icons-png.flaticon.com/512/3075/3075977.png',
                            '2' => 'https://cdn-icons-png.flaticon.com/512/1046/1046784.png',
                            'default' => 'https://cdn-icons-png.flaticon.com/512/3170/3170733.png'
                        ];

                        $prods = $conn->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($prods as $p):
                            $id_p = $p['id_producto'];
                            $img = $fotos[$id_p] ?? $fotos['default'];
                        ?>
                            <div class="col">
                                <div class="card border-cartoon menu-card p-3 text-center" 
                                     data-id="<?= $id_p ?>" 
                                     data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>" 
                                     data-precio="<?= $p['precio'] ?>">
                                    <img src="<?= $img ?>" style="height: 100px; object-fit: contain;" class="mx-auto mb-2 pointer-events-none" alt="Plato">
                                    <h5 class="fw-bold m-0 text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                                    <span class="badge bg-danger fs-6 border-cartoon mt-2">Bs. <?= number_format($p['precio'], 2) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- DETALLE DEL PEDIDO -->
            <div class="col-lg-5">
                <div class="card border-cartoon p-4 bg-warning">
                    <h4 class="fw-bold text-dark mb-3">
                        🛒 2. Detalle del Pedido
                    </h4>

                    <form method="POST">
                        <input type="hidden" name="id_producto" id="id_producto" required>
                        
                        <div class="mb-3">
                            <label class="fw-bold">Nombre del Cliente:</label>
                            <input type="text" name="cliente" id="cliente_input" class="form-control border-cartoon fw-bold" placeholder="Ej. Pedro Picapiedra" required>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold">Cantidad:</label>
                            <input type="number" name="cantidad" id="cantidad_input" class="form-control border-cartoon fw-bold" value="1" min="1" required>
                        </div>

                        <!-- TICKET DE COMPRA -->
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
                            ¡CONFIRMAR VENTA! 🔥
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- REPORTES Y PEDIDOS -->
        <div class="row mt-4 g-4">
            <?php if ($db_user === 'admin_candelaria'): ?>
                <div class="col-12">
                    <div class="card border-cartoon p-4 bg-white">
                        <h4 class="fw-bold text-danger mb-3">📊 Reporte General de Ventas (Vista Administrador)</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center border-cartoon">
                                <thead class="table-warning border-cartoon">
                                    <tr>
                                        <th>Cajero</th>
                                        <th>Total Pedidos</th>
                                        <th>Total Recaudado</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-bold">
                                    <?php
                                    try {
                                        $resumen = $conn->query("SELECT * FROM resumen_ventas_admin")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($resumen as $r) {
                                            $cajero = $r['usuario_registro'] ?? $r['cajero'] ?? 'Desconocido';
                                            $total_ped = $r['total_pedidos'] ?? $r['pedidos'] ?? 0;
                                            $recaudado = $r['total_recaudado'] ?? $r['total'] ?? 0;
                                            echo "<tr>
                                                <td><span class='badge bg-danger border-cartoon fs-6'>{$cajero}</span></td>
                                                <td>{$total_ped} pedido(s)</td>
                                                <td class='text-success fs-5'>Bs. ".number_format($recaudado, 2)."</td>
                                            </tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='3' class='text-danger'>Sin acceso al reporte general.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-12">
                <div class="card border-cartoon p-4 bg-white">
                    <h4 class="fw-bold text-dark mb-3">🕒 Últimos Pedidos Registrados</h4>
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle border-cartoon">
                            <thead class="table-dark">
                                <tr>
                                    <th># Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total Pagado</th>
                                    <th>Atendido Por</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody class="fw-bold">
                                <?php
                                try {
                                    $pedidos = $conn->query("SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                                    if (count($pedidos) > 0) {
                                        foreach ($pedidos as $ped) {
                                            echo "<tr>
                                                <td><span class='badge bg-warning text-dark border-cartoon'>#{$ped['id_pedido']}</span></td>
                                                <td class='text-start ps-4'>".htmlspecialchars($ped['cliente'])."</td>
                                                <td class='text-success fs-6'>Bs. ".number_format($ped['total'], 2)."</td>
                                                <td>{$ped['usuario_registro']}</td>
                                                <td class='text-muted fs-7'>{$ped['fecha']}</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-muted py-3'>No hay pedidos registrados aún.</td></tr>";
                                    }
                                } catch (Exception $e) {
                                    echo "<tr><td colspan='5' class='text-muted py-3'>No hay pedidos registrados aún.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
    const btnConfirmar = document.getElementById('btn_confirmar');

    document.querySelectorAll('.menu-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.menu-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            productoSeleccionado = {
                id: this.dataset.id,
                nombre: this.dataset.nombre,
                precio: parseFloat(this.dataset.precio)
            };

            document.getElementById('id_producto').value = productoSeleccionado.id;
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