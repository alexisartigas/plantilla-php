<?php

use Parzibyte\Modelos\ModeloUsuarios;
use Parzibyte\Redirect;
use Parzibyte\Servicios\Seguridad;
use Parzibyte\Servicios\SesionService;
use Phroute\Phroute\RouteCollector;

$enrutador = new RouteCollector();

$enrutador->filter("logueado", function () {
    if (empty(SesionService::leer("correoUsuario"))) {
        return Redirect::to("/login")->do();
    }
    return null; # Cualquier otra cosa que NO sea null va a prevenir la ejecución de la ruta
});

$enrutador->filter("administrador", function () {
    $idUsuario = SesionService::leer("idUsuario");
    if (!$idUsuario || !ModeloUsuarios::uno($idUsuario)->administrador) {
        # TODO: aquí haz la redirección a donde el usuario deba ir
        return Redirect::to("/perfil/cambiar-password")->do();
    }
    return null; # Cualquier otra cosa que NO sea null va a prevenir la ejecución de la ruta
});

$enrutador->filter("token_csrf", function () {
    $tieneTokenGet = isset($_GET["token_csrf"]);
    $tieneTokenPost = isset($_POST["token_csrf"]);
    if (!$tieneTokenGet && !$tieneTokenPost) exit("Falta el token CSRF");
    $tokenUsuario = $tieneTokenGet ? $_GET["token_csrf"] : $_POST["token_csrf"];
    if (!Seguridad::coincideTokenCSRF($tokenUsuario)) {
        echo "No coincide el token CSRF proporcionado";
        return false;
    } else {
        return null;
    }
});

$enrutador
    ->group(["before" => "logueado"], function ($enrutadorVistasPrivadas) {
        $enrutadorVistasPrivadas
            ->get("/perfil/cambiar-password", ["Parzibyte\Controladores\ControladorUsuarios", "perfilCambiarPassword"])
            ->post("/perfil/cambiar-password", ["Parzibyte\Controladores\ControladorUsuarios", "perfilGuardarPassword"])
            ->get("/logout", ["Parzibyte\Controladores\ControladorLogin", "logout"]);
    });
/**
 * Estos son métodos o vistas que solo puede ver un administrador
 * que obviamente está logueado
 */
$enrutador
    ->group(["before" => ["administrador"]], function ($enrutadorVistasPrivadas) {
        $enrutadorVistasPrivadas->group(["before" => ["token_csrf"]], function ($enrutadorToken) {
            $enrutadorToken
                ->get(
                    "/usuarios/removerAdministrador/{idUsuario}",
                    ["Parzibyte\Controladores\ControladorUsuarios", "removerAdministrador"]
                );
        });
        $enrutadorVistasPrivadas
            ->get("/ajustes", ["Parzibyte\Controladores\ControladorAjustes", "index"])
            ->get("/usuarios", ["Parzibyte\Controladores\ControladorUsuarios", "index"])
            ->get("/usuarios/agregar", ["Parzibyte\Controladores\ControladorUsuarios", "agregar"])
            ->get("/usuarios/hacerAdministrador/{idUsuario}", ["Parzibyte\Controladores\ControladorUsuarios", "hacerAdministrador"])
            ->post("/usuarios/eliminar", ["Parzibyte\Controladores\ControladorUsuarios", "eliminar"])
            ->get("/usuarios/eliminar/{idUsuario}", ["Parzibyte\Controladores\ControladorUsuarios", "confirmarEliminacion"])
            ->post("/usuarios/guardar", ["Parzibyte\Controladores\ControladorUsuarios", "guardar"]);
    });

$enrutador->post("/login", ["Parzibyte\Controladores\ControladorLogin", "login"]);
$enrutador->get("/login", ["Parzibyte\Controladores\ControladorLogin", "index"]);
$enrutador->get("/registro", ["Parzibyte\Controladores\ControladorUsuarios", "registrar"]);
$enrutador->post("/usuarios/registro", ["Parzibyte\Controladores\ControladorUsuarios", "registro"]);

$enrutador->get("/usuarios/verificar/{token}", ["Parzibyte\Controladores\ControladorUsuarios", "verificar"]);
# Cuando quieren resetear
$enrutador->get("/usuarios/solicitar-nueva-password", ["Parzibyte\Controladores\ControladorUsuarios", "formularioSolicitarNuevaPassword"]);
$enrutador->post("/usuarios/solicitar-nueva-password", ["Parzibyte\Controladores\ControladorUsuarios", "solicitarNuevaPassword"]);
# Cuando ya les llegó el correo
$enrutador->get("/usuarios/restablecer-password/{token}", ["Parzibyte\Controladores\ControladorUsuarios", "formularioRestablecerPassword"]);
$enrutador->post("/usuarios/restablecer-password", ["Parzibyte\Controladores\ControladorUsuarios", "restablecerPassword"]);
# Reenviar correo de registro
$enrutador->get("/usuarios/reenviar-correo", ["Parzibyte\Controladores\ControladorUsuarios", "solicitarReenvioCorreo"]);
$enrutador->post("/usuarios/reenviar-correo", ["Parzibyte\Controladores\ControladorUsuarios", "reenviarCorreo"]);

$enrutador->get("/", ["Parzibyte\Controladores\ControladorLogin", "index"]);

return $enrutador;
