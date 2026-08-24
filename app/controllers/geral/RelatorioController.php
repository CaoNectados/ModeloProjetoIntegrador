<?php

namespace app\controllers\geral;

use app\core\Controller;
use app\repositories\ProtetorRepository;
use app\services\RelatorioService;
use Exception;

class RelatorioController extends Controller
{
    private RelatorioService $relatorioService;
    private ProtetorRepository $protetorRepo;

    // Usado por: instanciado pelo Router para a rota GET /relatorios
    public function __construct()
    {
        $this->autenticacaoRequired(['protetor', 'ong']);
        $this->relatorioService = new RelatorioService();
        $this->protetorRepo = new ProtetorRepository();
    }

    // Usado por: rota GET /relatorios (RF 12 - relatório individual da ONG/Protetor)
    public function index(): void
    {
        try {
            $protetorId = $this->obterProtetorIdAutenticado();
            if ($protetorId <= 0) {
                throw new Exception('Perfil de protetor não encontrado para este usuário.');
            }

            $mes = (isset($_GET['mes']) && $_GET['mes'] !== '') ? (int) $_GET['mes'] : null;
            $ano = (isset($_GET['ano']) && $_GET['ano'] !== '') ? (int) $_GET['ano'] : null;

            $relatorio = $this->relatorioService->obterRelatorioProtetor($protetorId, $mes, $ano);

            $this->view('relatorios/relatorios', [
                'titulo'    => 'Relatórios',
                'relatorio' => $relatorio,
                'mesFiltro' => $mes,
                'anoFiltro' => $ano,
            ]);
        } catch (Exception $e) {
            $this->redirecionarComMensagem('erro', 'Erro ao carregar relatórios: ' . $e->getMessage(), '/perfil');
        }
    }

    // Usado por: index() — resolve o protetor_id a partir da SESSÃO (nunca de input do
    // usuário), garantindo que cada ONG/Protetor só consiga ver os próprios dados nas
    // queries do relatório. Mesmo padrão usado em AnimalController::obterProtetorIdAutenticado().
    private function obterProtetorIdAutenticado(): int
    {
        if (isset($_SESSION['protetor_id']) && (int) $_SESSION['protetor_id'] > 0) {
            return (int) $_SESSION['protetor_id'];
        }

        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        if ($usuarioId > 0) {
            $protetor = $this->protetorRepo->buscarPorUsuarioId($usuarioId);
            if ($protetor && isset($protetor['protetor_id'])) {
                $_SESSION['protetor_id'] = (int) $protetor['protetor_id'];
                return (int) $protetor['protetor_id'];
            }
        }

        return 0;
    }
}
