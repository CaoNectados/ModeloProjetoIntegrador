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
        $relatorio = $this->relatorioService->obterRelatorioAdmin($this->filtrosDaRequisicao());

        $this->view('admin/relatorios', [
            'titulo'    => 'Relatórios e Estatísticas',
            'relatorio' => $relatorio,
        ]);
    }

    /**
     * Exporta o relatório geral do sistema em CSV, respeitando os mesmos filtros da tela
     * (protetor/período/status). Usado tanto pelo botão "Exportar CSV" do dashboard (sem
     * filtros = relatório completo) quanto pelo botão equivalente na própria tela de
     * relatórios (que reenvia os filtros ativos via query string).
     */
    // Usado por: rota GET /admin/relatorios/exportar-csv
    public function exportarCsv(): void
    {
        $relatorio = $this->relatorioService->obterRelatorioAdmin($this->filtrosDaRequisicao());

        $nomeArquivo = 'relatorio-caonectados-' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

        $saida = fopen('php://output', 'w');

        // BOM UTF-8: sem isso o Excel no Windows interpreta os acentos errado.
        fwrite($saida, "\xEF\xBB\xBF");

        // Separador ';' (não ',') porque o Excel em pt-BR usa vírgula como separador
        // decimal e trataria um CSV separado por vírgula como uma única coluna.
        fputcsv($saida, ['Relatório Geral do Sistema - CãoNectados'], ';');
        fputcsv($saida, ['Gerado em', date('d/m/Y H:i')], ';');
        fputcsv($saida, []);

        fputcsv($saida, ['RESUMO'], ';');
        fputcsv($saida, ['Total de Animais na Plataforma', $relatorio['kpis']['total_animais']], ';');
        fputcsv($saida, ['Total de Adoções Realizadas', $relatorio['kpis']['total_adocoes']], ';');
        fputcsv($saida, ['Total de ONGs/Protetores Ativos', $relatorio['kpis']['total_entidades']], ';');
        fputcsv($saida, []);

        fputcsv($saida, ['RANKING POR ENTIDADE'], ';');
        fputcsv($saida, ['Entidade', 'Tipo', 'Animais Cadastrados', 'Animais Adotados', 'Taxa de Sucesso (%)'], ';');
        foreach ($relatorio['ranking'] as $linha) {
            fputcsv($saida, [
                $linha['nome_fantasia'],
                strtoupper($linha['tipo_documento']),
                $linha['total_cadastrados'],
                $linha['total_adotados'],
                number_format($linha['taxa_sucesso'], 1, ',', ''),
            ], ';');
        }
        fputcsv($saida, []);

        fputcsv($saida, ['DEMOGRAFIA POR ESPÉCIE'], ';');
        fputcsv($saida, ['Espécie', 'Cadastrados', 'Adotados'], ';');
        foreach ($relatorio['especies'] as $linha) {
            fputcsv($saida, [$linha['rotulo'], $linha['total_cadastrados'], $linha['total_adotados']], ';');
        }
        fputcsv($saida, []);

        fputcsv($saida, ['DEMOGRAFIA POR PORTE'], ';');
        fputcsv($saida, ['Porte', 'Cadastrados', 'Adotados'], ';');
        foreach ($relatorio['portes'] as $linha) {
            fputcsv($saida, [$linha['rotulo'], $linha['total_cadastrados'], $linha['total_adotados']], ';');
        }

        fclose($saida);
        exit;
    }

    // Usado por: index() e exportarCsv() (uso interno) — os mesmos 3 filtros em GET
    // (protetor_id/periodo/status), reaproveitados pela tela e pela exportação.
    private function filtrosDaRequisicao(): array
    {
        return [
            'periodo'     => $_GET['periodo'] ?? 'todos',
            'protetor_id' => $_GET['protetor_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
        ];
    }
}
