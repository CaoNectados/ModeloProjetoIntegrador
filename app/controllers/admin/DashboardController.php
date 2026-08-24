<?php

namespace app\controllers\admin;

use app\repositories\ProtetorRepository;
use app\repositories\UsuarioRepository;
use app\repositories\RelatorioRepository;
use app\repositories\DenunciaRepository;
use DateTimeImmutable;

class DashboardController extends AdminBaseController
{
    // Usado por: rota GET /admin/dashboard
    public function index()
    {
        $protetorRepo = new ProtetorRepository();
        $usuarioRepo = new UsuarioRepository();
        $relatorioRepo = new RelatorioRepository();
        $denunciaRepo = new DenunciaRepository();

        $inicioDoMes = (new DateTimeImmutable())->format('Y-m-01 00:00:00');

        $porStatus = $relatorioRepo->contarAnimaisPorStatusGlobal();

        $this->view('admin/dashboard', [
            'titulo' => 'Dashboard',
            'stats'  => [
                'cadastros_pendentes'       => $protetorRepo->contarPendentes(),
                'denuncias_abertas'         => $denunciaRepo->contarAbertas(),
                'adocoes_concluidas_no_mes' => $relatorioRepo->contarAdocoesNoPeriodo($inicioDoMes, null),
                'animais_disponiveis'       => $porStatus['disponivel'],
                'animais_adotados'          => $porStatus['adotado'],
                'usuarios_ativos'           => $usuarioRepo->contarAtivos(),
            ],
        ]);
    }
}
