<?php require_once __DIR__ . '/../templates/header.php'; ?>

<div class="space-y-6 pb-10">
    <div class="my-4 text-center">
        <h1 class="text-3xl font-bold font-shantell text-primary">Denúncias</h1>
        <p class="text-xs text-text-muted mt-1">Listagem básica das denúncias em aberto ou em análise</p>
    </div>

    <div class="card-padrao bg-white p-4 border-l-4 border-laranja-1">
        <p class="text-xs text-gray-600">
            ⚠️ Esta tela ainda mostra apenas os dados existentes — o fluxo de moderação
            (analisar, aprovar, arquivar denúncia) faz parte de uma etapa futura.
        </p>
    </div>

    <?php if (empty($denuncias)): ?>
        <div class="card-padrao bg-white text-center py-16">
            <span class="text-5xl block mb-3 opacity-40">🕊️</span>
            <p class="text-text-muted text-base font-semibold">Nenhuma denúncia em aberto no momento.</p>
        </div>
    <?php else: ?>
        <div class="card-padrao bg-white overflow-x-auto p-0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-xs uppercase text-gray-600">
                        <th class="px-4 py-3 font-bold">Data</th>
                        <th class="px-4 py-3 font-bold">Denunciante</th>
                        <th class="px-4 py-3 font-bold">Denunciado</th>
                        <th class="px-4 py-3 font-bold">Perfil</th>
                        <th class="px-4 py-3 font-bold">Motivo</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                        $motivosRotulo = [
                            'maus_tratos' => 'Maus-tratos',
                            'abandono'    => 'Abandono',
                            'fraude'      => 'Fraude',
                            'assedio'     => 'Assédio',
                            'outro'       => 'Outro',
                        ];
                        $statusRotulo = [
                            'aberta'     => ['label' => 'Aberta', 'classe' => 'text-erro'],
                            'em_analise' => ['label' => 'Em Análise', 'classe' => 'text-laranja-1'],
                        ];
                    ?>
                    <?php foreach ($denuncias as $d): ?>
                        <?php $status = $statusRotulo[$d['status_denuncia']] ?? ['label' => ucfirst($d['status_denuncia']), 'classe' => 'text-gray-500']; ?>
                        <tr class="hover:bg-gray-50 transition align-top">
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap"><?= !empty($d['criado_em']) ? date('d/m/Y', strtotime($d['criado_em'])) : '-' ?></td>
                            <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($d['denunciante_nome'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($d['denunciado_nome'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(ucfirst($d['perfil_denunciado'])) ?></td>
                            <td class="px-4 py-3 text-gray-800">
                                <span class="font-medium"><?= htmlspecialchars($motivosRotulo[$d['motivo']] ?? ucfirst($d['motivo'])) ?></span>
                                <p class="text-xs text-gray-500 mt-0.5 max-w-xs truncate" title="<?= htmlspecialchars($d['descricao']) ?>"><?= htmlspecialchars($d['descricao']) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-xs <?= $status['classe'] ?>"><?= $status['label'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
