<?php

namespace app\controllers\admin;

use app\repositories\DenunciaRepository;

/**
 * Listagem básica de denúncias abertas — sem ações de moderação ainda (analisar/aprovar/
 * arquivar). Existe só pra dar visibilidade real ao admin sobre o que já está no banco;
 * o fluxo completo de tratamento fica pra uma etapa futura.
 */
class DenunciaController extends AdminBaseController
{
    private DenunciaRepository $denunciaRepo;

    // Usado por: instanciado pelo Router para a rota GET /admin/denuncias
    public function __construct()
    {
        parent::__construct();
        $this->denunciaRepo = new DenunciaRepository();
    }

    // Usado por: rota GET /admin/denuncias
    public function index(): void
    {
        $this->view('admin/denuncias', [
            'titulo'    => 'Denúncias',
            'denuncias' => $this->denunciaRepo->listarAbertas(),
        ]);
    }
}
