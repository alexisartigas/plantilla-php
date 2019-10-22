<?php

namespace Parzibyte\Servicios;

use Parzibyte\Modelos\ModeloUsuarios;
use Twig\Environment as Twig_Environment;
use Twig\TwigFunction as Twig_SimpleFunction;
use Twig\Loader\FilesystemLoader;

class Twig
{

    public static function obtener()
    {
        $loader = new FilesystemLoader(DIRECTORIO_RAIZ . "/vistas");
        $cachearTwig = boolval(Comun::env("HABILITAR_CACHE_TWIG", false));
        $rutaCacheTwig = false;
        if ($cachearTwig) {
            $rutaCacheTwig = Comun::env("RUTA_CACHE_TWIG", "cache_twig");
        }
        $twig = new Twig_Environment($loader, [
            "cache" => $rutaCacheTwig,
        ]);
        $twig->addGlobal("URL_DIRECTORIO_PUBLICO", URL_DIRECTORIO_PUBLICO);
        $twig->addGlobal("RUTA_API", RUTA_API);
        $twig->addGlobal("URL_RAIZ", URL_RAIZ);
        $twig->addGlobal("NOMBRE_APLICACION", NOMBRE_APLICACION);
        $twig->addGlobal("AUTOR", AUTOR);
        $twig->addGlobal("WEB_AUTOR", WEB_AUTOR);
        $twig->addGlobal("TIEMPO_ACTUAL", time());
        $twig->addGlobal("USUARIO_LOGUEADO", SesionService::leer("correoUsuario"));
        $usuario = ModeloUsuarios::uno(SesionService::leer("idUsuario"));
        $twig->addGlobal("USUARIO_ADMIN", $usuario != null && $usuario->administrador);
        $twig->addFunction(new Twig_SimpleFunction("sesion_flash", function ($clave) {
            return SesionService::flash($clave);
        }));
        $twig->addFunction(new Twig_SimpleFunction("token_csrf", function () {
            return \Parzibyte\Servicios\Seguridad::obtenerTokenCSRF();
        }));
        return $twig;
    }
}
