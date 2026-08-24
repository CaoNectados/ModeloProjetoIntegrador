<?php

namespace app\repositories;

use app\core\BaseRepository;
use PDO;

/**
 * Repositório mínimo de denúncias — só leitura por enquanto. O fluxo de moderação (analisar,
 * aprovar, arquivar) ainda não existe no sistema; isso aqui só permite o admin puxar e
 * enxergar as denúncias que já estão no banco (card do dashboard + listagem básica em
 * /admin/denuncias), sem nenhuma ação de workflow ainda.
 */
class DenunciaRepository extends BaseRepository
{
    // Usado por: DashboardController::index() — card "Denúncias em Aberto"
    public function contarAbertas(): int
    {
        $sql = "SELECT COUNT(*) FROM DENUNCIA WHERE status_denuncia IN ('aberta', 'em_analise')";
        return (int) $this->db->query($sql)->fetchColumn();
    }

    // Usado por: DenunciaController::index() (admin) — listagem básica, mais recentes primeiro
    public function listarAbertas(int $limite = 50): array
    {
        $sql = "SELECT
                    d.denuncia_id,
                    d.motivo,
                    d.descricao,
                    d.perfil_denunciado,
                    d.status_denuncia,
                    d.criado_em,
                    denunciante.nome AS denunciante_nome,
                    denunciado.nome AS denunciado_nome
                FROM DENUNCIA d
                INNER JOIN USUARIO denunciante ON denunciante.usuario_id = d.denunciante_id
                INNER JOIN USUARIO denunciado ON denunciado.usuario_id = d.denunciado_id
                WHERE d.status_denuncia IN ('aberta', 'em_analise')
                ORDER BY d.criado_em DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
