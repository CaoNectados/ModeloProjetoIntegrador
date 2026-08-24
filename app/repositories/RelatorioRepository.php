<?php

namespace app\repositories;

use app\core\BaseRepository;
use PDO;

class RelatorioRepository extends BaseRepository
{
    // ==========================================================
    // ESCOPO ONG/PROTETOR (individual, sempre filtrado por protetor_id)
    // ==========================================================

    // Usado por: RelatorioService::obterRelatorioProtetor() — volume de animais por status,
    // sempre com as 4 chaves do ENUM presentes (mesmo quando zero). Filtra pela data de
    // CADASTRO (criado_em) quando um período é informado.
    public function contarAnimaisPorStatus(int $protetorId, ?string $dataInicio, ?string $dataFim): array
    {
        $params = [':protetor_id' => $protetorId];
        $sql = "SELECT status, COUNT(*) AS total
                FROM ANIMAL
                WHERE protetor_id = :protetor_id
                  AND deletado_em IS NULL"
                . $this->clausulaPeriodo('criado_em', $dataInicio, $dataFim, $params)
                . " GROUP BY status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $contagem = ['disponivel' => 0, 'em_analise' => 0, 'adotado' => 0, 'desativado' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $contagem[$linha['status']] = (int) $linha['total'];
        }

        return $contagem;
    }

    /**
     * Tempo médio (em dias) entre o cadastro do animal e a adoção.
     *
     * Usa atualizado_em como "data da adoção" porque é o único campo realmente preenchido
     * nesse momento pelo fluxo já existente (AnimalController::status() ->
     * AnimalRepository::alterarStatus(), que grava atualizado_em = CURRENT_TIMESTAMP a cada
     * troca de status). SOLICITACAO_ADOCAO.data_finalizacao e HISTORICO_STATUS_ANIMAL existem
     * no schema mas nenhuma parte do sistema grava neles ainda — usá-los aqui sempre
     * retornaria vazio.
     */
    // Usado por: RelatorioService::obterRelatorioProtetor()
    public function calcularTempoMedioAdocaoDias(int $protetorId, ?string $dataInicio, ?string $dataFim): ?float
    {
        $params = [':protetor_id' => $protetorId];
        $sql = "SELECT AVG(DATEDIFF(atualizado_em, criado_em)) AS media_dias
                FROM ANIMAL
                WHERE protetor_id = :protetor_id
                  AND status = 'adotado'
                  AND deletado_em IS NULL
                  AND atualizado_em IS NOT NULL"
                . $this->clausulaPeriodo('atualizado_em', $dataInicio, $dataFim, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $media = $stmt->fetchColumn();

        return $media !== null ? round((float) $media, 1) : null;
    }

    // Usado por: RelatorioService::obterRelatorioProtetor() — espécies mais adotadas
    public function perfilEspeciesAdotadas(int $protetorId, ?string $dataInicio, ?string $dataFim): array
    {
        $params = [':protetor_id' => $protetorId];
        $sql = "SELECT e.nome AS rotulo, COUNT(*) AS total
                FROM ANIMAL a
                INNER JOIN RACA r ON r.raca_id = a.raca_id
                INNER JOIN ESPECIE e ON e.especie_id = r.especie_id
                WHERE a.protetor_id = :protetor_id
                  AND a.status = 'adotado'
                  AND a.deletado_em IS NULL"
                . $this->clausulaPeriodo('a.atualizado_em', $dataInicio, $dataFim, $params)
                . " GROUP BY e.especie_id, e.nome
                    ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Usado por: RelatorioService::obterRelatorioProtetor() — portes mais adotados
    public function perfilPortesAdotados(int $protetorId, ?string $dataInicio, ?string $dataFim): array
    {
        $params = [':protetor_id' => $protetorId];
        $sql = "SELECT porte, COUNT(*) AS total
                FROM ANIMAL
                WHERE protetor_id = :protetor_id
                  AND status = 'adotado'
                  AND deletado_em IS NULL"
                . $this->clausulaPeriodo('atualizado_em', $dataInicio, $dataFim, $params)
                . " GROUP BY porte
                    ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Usado por: RelatorioService::obterRelatorioProtetor() — faixa etária dos adotados
    // (calculada a partir de dt_nasc; animais sem data de nascimento caem em "Não informado")
    public function perfilFaixaEtariaAdotados(int $protetorId, ?string $dataInicio, ?string $dataFim): array
    {
        $params = [':protetor_id' => $protetorId];
        $sql = "SELECT
                    CASE
                        WHEN dt_nasc IS NULL THEN 'Não informado'
                        WHEN TIMESTAMPDIFF(MONTH, dt_nasc, CURDATE()) < 12 THEN 'Filhote (< 1 ano)'
                        WHEN TIMESTAMPDIFF(YEAR, dt_nasc, CURDATE()) BETWEEN 1 AND 3 THEN 'Jovem (1-3 anos)'
                        WHEN TIMESTAMPDIFF(YEAR, dt_nasc, CURDATE()) BETWEEN 4 AND 7 THEN 'Adulto (4-7 anos)'
                        ELSE 'Idoso (8+ anos)'
                    END AS rotulo,
                    COUNT(*) AS total
                FROM ANIMAL
                WHERE protetor_id = :protetor_id
                  AND status = 'adotado'
                  AND deletado_em IS NULL"
                . $this->clausulaPeriodo('atualizado_em', $dataInicio, $dataFim, $params)
                . " GROUP BY rotulo
                    ORDER BY total DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // ESCOPO ADMIN (global, com filtros opcionais de protetor/período/status)
    // ==========================================================

    // Usado por: RelatorioService::obterRelatorioAdmin() — KPIs superiores do dashboard
    public function kpisGlobais(array $filtros): array
    {
        $params = [];
        $where = "a.deletado_em IS NULL" . $this->clausulaFiltrosAnimal($filtros, $params, 'a');

        $sql = "SELECT
                    COUNT(*) AS total_animais,
                    SUM(CASE WHEN a.status = 'adotado' THEN 1 ELSE 0 END) AS total_adocoes
                FROM ANIMAL a
                WHERE $where";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_animais' => 0, 'total_adocoes' => 0];

        // Total de entidades ativas é uma contagem global "de agora" — não faz sentido
        // recortar por período/status de animal, então não usa os mesmos filtros acima.
        $totalEntidades = (int) $this->db->query(
            "SELECT COUNT(*) FROM PROTETOR WHERE validado = 1 AND deletado_em IS NULL"
        )->fetchColumn();

        return [
            'total_animais'   => (int) ($linha['total_animais'] ?? 0),
            'total_adocoes'   => (int) ($linha['total_adocoes'] ?? 0),
            'total_entidades' => $totalEntidades,
        ];
    }

    // Usado por: DashboardController::index() — "gráfico" de análise geral do dashboard
    // admin. Igual a contarAnimaisPorStatus(), mas sem recorte por protetor (visão global).
    public function contarAnimaisPorStatusGlobal(): array
    {
        $sql = "SELECT status, COUNT(*) AS total FROM ANIMAL WHERE deletado_em IS NULL GROUP BY status";
        $stmt = $this->db->query($sql);

        $contagem = ['disponivel' => 0, 'em_analise' => 0, 'adotado' => 0, 'desativado' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $contagem[$linha['status']] = (int) $linha['total'];
        }

        return $contagem;
    }

    // Usado por: DashboardController::index() — card "Adoções Concluídas no Mês". Usa
    // atualizado_em (data real da mudança de status), pelo mesmo motivo documentado em
    // calcularTempoMedioAdocaoDias(): é o único campo que o fluxo existente realmente grava.
    public function contarAdocoesNoPeriodo(?string $dataInicio, ?string $dataFim): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM ANIMAL
                WHERE status = 'adotado' AND deletado_em IS NULL"
                . $this->clausulaPeriodo('atualizado_em', $dataInicio, $dataFim, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Ranking de ONGs/Protetores: quem cadastrou mais animais e a taxa de sucesso
     * (adotados / cadastrados). Uma única query com LEFT JOIN + GROUP BY + SUM condicional
     * evita N+1 (uma query por protetor) — os filtros de período/status entram na cláusula
     * ON do JOIN (não no WHERE) para que protetores sem nenhum animal no recorte ainda
     * apareçam no ranking com total 0, em vez de sumirem da lista.
     */
    // Usado por: RelatorioService::obterRelatorioAdmin()
    public function rankingEntidades(array $filtros): array
    {
        $params = [];
        $condicaoJoin = "a.protetor_id = p.protetor_id AND a.deletado_em IS NULL"
            . $this->clausulaFiltrosAnimal($filtros, $params, 'a', incluirProtetor: false);

        $whereProtetor = "p.deletado_em IS NULL AND p.validado = 1";
        if (!empty($filtros['protetor_id'])) {
            $whereProtetor .= " AND p.protetor_id = :protetor_id";
            $params[':protetor_id'] = $filtros['protetor_id'];
        }

        $sql = "SELECT
                    p.protetor_id,
                    p.nome_fantasia,
                    p.tipo_documento,
                    COUNT(a.animal_id) AS total_cadastrados,
                    SUM(CASE WHEN a.status = 'adotado' THEN 1 ELSE 0 END) AS total_adotados
                FROM PROTETOR p
                LEFT JOIN ANIMAL a ON $condicaoJoin
                WHERE $whereProtetor
                GROUP BY p.protetor_id, p.nome_fantasia, p.tipo_documento
                ORDER BY total_cadastrados DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhas as &$linha) {
            $linha['total_cadastrados'] = (int) $linha['total_cadastrados'];
            $linha['total_adotados']    = (int) $linha['total_adotados'];
            $linha['taxa_sucesso']      = $linha['total_cadastrados'] > 0
                ? round($linha['total_adotados'] / $linha['total_cadastrados'] * 100, 1)
                : 0.0;
        }
        unset($linha);

        return $linhas;
    }

    // Usado por: RelatorioService::obterRelatorioAdmin() — espécies mais cadastradas vs. mais
    // adotadas na plataforma inteira (uma linha por espécie, duas colunas pra comparar)
    public function demografiaEspecies(array $filtros): array
    {
        $params = [];
        $where = "a.deletado_em IS NULL" . $this->clausulaFiltrosAnimal($filtros, $params, 'a');

        $sql = "SELECT
                    e.nome AS rotulo,
                    COUNT(*) AS total_cadastrados,
                    SUM(CASE WHEN a.status = 'adotado' THEN 1 ELSE 0 END) AS total_adotados
                FROM ANIMAL a
                INNER JOIN RACA r ON r.raca_id = a.raca_id
                INNER JOIN ESPECIE e ON e.especie_id = r.especie_id
                WHERE $where
                GROUP BY e.especie_id, e.nome
                ORDER BY total_cadastrados DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Usado por: RelatorioService::obterRelatorioAdmin() — portes mais cadastrados vs. mais
    // adotados na plataforma inteira
    public function demografiaPortes(array $filtros): array
    {
        $params = [];
        $where = "a.deletado_em IS NULL" . $this->clausulaFiltrosAnimal($filtros, $params, 'a');

        $sql = "SELECT
                    a.porte,
                    COUNT(*) AS total_cadastrados,
                    SUM(CASE WHEN a.status = 'adotado' THEN 1 ELSE 0 END) AS total_adotados
                FROM ANIMAL a
                WHERE $where
                GROUP BY a.porte
                ORDER BY total_cadastrados DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================================
    // Helpers privados de composição de WHERE (reaproveitados pelas queries acima)
    // ==========================================================

    // Usado por: todas as queries com filtro de período opcional (uso interno)
    private function clausulaPeriodo(string $coluna, ?string $dataInicio, ?string $dataFim, array &$params): string
    {
        $sql = '';
        if ($dataInicio !== null) {
            $sql .= " AND $coluna >= :data_inicio";
            $params[':data_inicio'] = $dataInicio;
        }
        if ($dataFim !== null) {
            $sql .= " AND $coluna < :data_fim";
            $params[':data_fim'] = $dataFim;
        }
        return $sql;
    }

    // Usado por: kpisGlobais(), rankingEntidades(), demografiaEspecies(), demografiaPortes()
    // (uso interno) — monta os filtros avançados do admin (protetor/status/período) sobre a
    // tabela ANIMAL. $incluirProtetor é desligado em rankingEntidades() porque lá o filtro de
    // protetor já é aplicado no WHERE da tabela PROTETOR, não faria sentido duplicar no JOIN.
    private function clausulaFiltrosAnimal(array $filtros, array &$params, string $alias, bool $incluirProtetor = true): string
    {
        $sql = '';
        if ($incluirProtetor && !empty($filtros['protetor_id'])) {
            $sql .= " AND $alias.protetor_id = :protetor_id";
            $params[':protetor_id'] = $filtros['protetor_id'];
        }
        if (!empty($filtros['status'])) {
            $sql .= " AND $alias.status = :status";
            $params[':status'] = $filtros['status'];
        }
        $sql .= $this->clausulaPeriodo("$alias.criado_em", $filtros['data_inicio'] ?? null, $filtros['data_fim'] ?? null, $params);

        return $sql;
    }
}
