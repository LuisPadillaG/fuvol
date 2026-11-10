<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Configuración por defecto de MySQL en XAMPP
$servidor = "localhost";
$usuario_db = "root";
$password_db = "";
$nombre_db = "fuvol";

$conexion = new mysqli($servidor, $usuario_db, $password_db, $nombre_db);

if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión con MySQL en XAMPP: " . $conexion->connect_error]);
    exit;
}

$conexion->set_charset("utf8mb4");

// Detectar automáticamente si la tabla se llama `usuarios` o `usuario`
$tabla = "usuarios";
$checkTabla = $conexion->query("SHOW TABLES LIKE 'usuarios'");
if ($checkTabla && $checkTabla->num_rows === 0) {
    $tabla = "usuario";
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$inputJSON = file_get_contents("php://input");
$datos = json_decode($inputJSON, true);

// 1. OBTENER DATOS DEL USUARIO (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'obtener') {
    $user = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';

    $stmt = $conexion->prepare("SELECT usuario, password, equipo_favorito FROM `$tabla` WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        echo json_encode($fila);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Usuario no encontrado"]);
    }
    $stmt->close();
    $conexion->close();
    exit;
}

// 2. PETICIONES POST (LOGIN, REGISTRO Y ACTUALIZACIÓN)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($datos['usuario']) ? trim($datos['usuario']) : '';
    $pass = isset($datos['password']) ? $datos['password'] : '';
    $equipo = isset($datos['equipo_favorito']) ? trim($datos['equipo_favorito']) : '';

    // INICIAR SESIÓN
    if ($accion === 'login') {
        $stmt = $conexion->prepare("SELECT usuario, password, equipo_favorito FROM `$tabla` WHERE usuario = ? AND password = ? LIMIT 1");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            echo json_encode($fila);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Usuario o contraseña incorrectos"]);
        }
        $stmt->close();
    }

    // REGISTRAR NUEVO USUARIO
    elseif ($accion === 'registro') {
        // Verificar si ya existe el usuario
        $check = $conexion->prepare("SELECT usuario FROM `$tabla` WHERE usuario = ? LIMIT 1");
        $check->bind_param("s", $user);
        $check->execute();
        $resCheck = $check->get_result();

        if ($resCheck->num_rows > 0) {
            http_response_code(400);
            echo json_encode(["error" => "Ese nombre de usuario ya está registrado"]);
            $check->close();
            $conexion->close();
            exit;
        }
        $check->close();

        $stmt = $conexion->prepare("INSERT INTO `$tabla` (usuario, password, equipo_favorito) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $pass, $equipo);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                "mensaje" => "Usuario registrado correctamente",
                "usuario" => $user,
                "password" => $pass,
                "equipo_favorito" => $equipo
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "No se pudo insertar el usuario en MySQL"]);
        }
        $stmt->close();
    }

    // ACTUALIZAR CONTRASEÑA Y EQUIPO FAVORITO
    elseif ($accion === 'actualizar') {
        $stmt = $conexion->prepare("UPDATE `$tabla` SET password = ?, equipo_favorito = ? WHERE usuario = ?");
        $stmt->bind_param("sss", $pass, $equipo, $user);

        if ($stmt->execute()) {
            echo json_encode([
                "mensaje" => "Datos actualizados correctamente",
                "usuario" => $user,
                "password" => $pass,
                "equipo_favorito" => $equipo
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error al actualizar los datos en MySQL"]);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Acción no válida"]);
    }
}

$conexion->close();
?>