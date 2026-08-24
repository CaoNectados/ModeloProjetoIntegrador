<?php

namespace app\services;

use app\repositories\RelatorioRepository;
use app\repositories\ProtetorRepository;
use DateTimeImmutable;

class RelatorioService
{
    private RelatorioRepository $relatorioRepo;
    private ProtetorRepository $protetorRepo;

    private const LABELS_STATUS = [
        'disponivel' => 'Disponível',
        'em_analise' => 'Em Análise',
        'adotado'    => 'Adotado',
        'desativado' => 'Desativado',
    ];

    private const LABELS_PORTE = [
        'pequeno' => 'Pequeno',
        'medio'   => 'Médio',
        'grande'  => 'Grande',
    ];

    public function __construct()
    {
        $this->relatorioRepo = new RelatorioRepository();
        $this->protetorRepo = new ProtetorRepository();
    }

    // Usado por: RelatorioController (geral) — relatório individual da ONG/Protetor logada.
    // $protetorId sempre vem do controller resolvido a partir da SESSÃO, nunca de input do
    // usuário — é isso que garante que cada ONG/Protetor só veja os próprios dados.
    public function obterRelatorioProtetor(int $protetorId, ?int $mes, ?int $ano): array
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodoMesAno($mes, $ano);

        $porStatus = $this->relatorioRepo->contarAnimaisPorStatus($protetorId, $dataInicio, $dataFim);

        $cardsStatus = [];
        foreach (self::LABELS_STATUS as $chave => $rotulo) {
            $cardsStatus[] = ['status' => $chave, 'rotulo' => $rotulo, 'total' => $porStatus[$chave]];
        }

        $portes = $this->relatorioRepo->perfilPortesAdotados($protetorId, $dataInicio, $dataFim);
        foreach ($portes as &$linha) {
            $linha['rotulo'] = self::LABELS_PORTE[$linha['porte']] ?? ucfirst($linha['porte']);
        }
        unset($linha);

        return [
            'cards_status'      => $cardsStatus,
            'total_animais'     => array_sum($porStatus),
            'total_adotados'    => $porStatus['adotado'],
            'tempo_medio_dias'  => $this->relatorioRepo->calcularTempoMedioAdocaoDias($protetorId, $dataInicio, $dataFim),
            'especies_adotadas' => $this->relatorioRepo->perfilEspeciesAdotadas($protetorId, $dataInicio, $dataFim),
            'portes_adotados'   => $portes,
            'faixas_etarias'    => $this->relatorioRepo->perfilFaixaEtariaAdotados($protetorId, $dataInicio, $dataFim),
        ];
    }

    // Usado por: RelatorioController (admin) — dashboard analítico global.
    // $filtrosBrutos vem direto de $_GET (protetor_id, periodo, status); aqui é validado e
    // traduzido pros parâmetros reais das queries (datas concretas, enum de status válido).
    public function obterRelatorioAdmin(array $filtrosBrutos): array
    {
        $periodo = $filtrosBrutos['periodo'] ?? 'todos';
        [$dataInicio, $dataFim] = $this->resolverPeriodoAdmin($periodo);

        $protetorId = !empty($filtrosBrutos['protetor_id']) ? (int) $filtrosBrutos['protetor_id'] : null;

        $status = $filtrosBrutos['status'] ?? null;
        if (!array_key_exists((string) $status, self::LABELS_STATUS)) {
            $status = null; // ignora valor de status desconhecido/forjado em vez de quebrar a query
        }

        $filtros = [
            'protetor_id' => $protetorId,
            'status'      => $status,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim,
        ];

        $portes = $this->relatorioRepo->demografiaPortes($filtros);
        foreach ($portes as &$linha) {
            $linha['rotulo'] = self::LABELS_PORTE[$linha['porte']] ?? ucfirst($linha['porte']);
        }
        unset($linha);

        return [
            'kpis'              => $this->relatorioRepo->kpisGlobais($filtros),
            'ranking'           => $this->relatorioRepo->rankingEntidades($filtros),
            'especies'          => $this->relatorioRepo->demografiaEspecies($filtros),
            'portes'            => $portes,
            'protetores_filtro' => $this->protetorRepo->listarValidados(),
            'status_opcoes'     => self::LABELS_STATUS,
            'filtros_aplicados' => [
                'periodo'     => $periodo,
                'protetor_id' => $protetorId,
                'status'      => $status,
            ],
        ];
    }

    // Usado por: obterRelatorioProtetor() (uso interno) — converte mês/ano (ou a ausência
    // deles) num intervalo [inicio, fim) de datas concretas, pra query nunca precisar
    // envolver a coluna em YEAR()/MONTH() (o que impediria o uso de índice em criado_em).
    private function resolverPeriodoMesAno(?int $mes, ?int $ano): array
    {
        if ($ano === null) {
            return [null, null];
        }

        if ($mes === null) {
            return [
                sprintf('%04d-01-01 00:00:00', $ano),
                sprintf('%04d-01-01 00:00:00', $ano + 1),
            ];
        }

        $mes = max(1, min(12, $mes));
        $inicio = sprintf('%04d-%02d-01 00:00:00', $ano, $mes);
        $fim = date('Y-m-01 00:00:00', mktime(0, 0, 0, $mes + 1, 1, $ano));

        return [$inicio, $fim];
    }

    // Usado por: obterRelatorioAdmin() (uso interno)
    private function resolverPeriodoAdmin(string $periodo): array
    {
        $agora = new DateTimeImmutable();

        return match ($periodo) {
            '30dias'    => [$agora->modify('-30 days')->format('Y-m-d 00:00:00'), null],
            'ano_atual' => [$agora->format('Y-01-01 00:00:00'), null],
            'mes_atual' => [$agora->format('Y-m-01 00:00:00'), null],
            default     => [null, null],
        };
    }
}
