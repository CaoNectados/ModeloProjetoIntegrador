<?php

namespace app\controllers\admin;

use app\services\RelatorioService;

class RelatorioController extends AdminBaseController
{
    private RelatorioService $relatorioService;

    // Usado por: instanciado pelo Router para a rota GET /admin/relatorios
    public function __construct()
    {
        parent::__construct(); // AdminBaseController já restringe a ['administrador']
        $this->relatorioService = new RelatorioService();
    }

    // Usado por: rota GET /admin/relatorios (RF 12 - dashboard analítico consolidado)
    public function index(): void
    {
        $filtros = [
            'periodo'     => $_GET['periodo'] ?? 'todos',
            'protetor_id' => $_GET['protetor_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
        ];

        $relatorio = $this->relatorioService->obterRelatorioAdmin($filtros);

        $this->view('admin/relatorios', [
            'titulo'    => 'Relatórios e Estatísticas',
            'relatorio' => $relatorio,
        ]);
    }
}
