<?php

namespace app\core;

class Controller
{

    public function view(string $view, ?array $data = null)
    {

        if ($data) {
            extract($data);
        }

        $path = __DIR__ . "/../views/$view.php";

        if (file_exists($path)) {
            require_once $path;
        } else {
            print 'A view solicitada não foi encontrada: ' . $view;
        }
    }

    public function redirect(string $url)
    {
        header('location: ' . $url);
        exit();
    }

    public function autenticacaoRequired()
    {
        if (!isset($_SESSION['usuario_logado'])) {
            $this->redirect(URL_BASE . '/login');
        }

        return true;
    }

    public function adminRequired()
    {
        if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado']->getPerfil() !== 'admin') {
            $this->redirect(URL_BASE . '/login');
        }

        return true;
    }
}