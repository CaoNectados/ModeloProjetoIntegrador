<?php
require_once __DIR__ . '/../templates/header.php';

$urlBase = defined('URL_BASE') ? rtrim(URL_BASE, '/') : '';
$relatorio = $relatorio ?? [];
$mesFiltro = $mesFiltro ?? null;
$anoFiltro = $anoFiltro ?? null;

$mesesNomes = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$anoAtual = (int) date('Y');
$anosDisponiveis = range($anoAtual, $anoAtual - 5);

$iconesStatus = [
    'disponivel' => '🐾',
    'em_analise' => '🔍',
    'adotado'    => '❤️',
    'desativado' => '⏸️',
];

$nomeEntidade = $_SESSION['usuario_nome'] ?? 'Minha página';
$periodoRotulo = $anoFiltro !== null
    ? ($mesFiltro !== null ? ($mesesNomes[$mesFiltro] . '/' . $anoFiltro) : (string) $anoFiltro)
    : 'Todo o período';
?>

<!-- Não existe biblioteca de PDF no projeto (composer.json só tem phpmailer) — em vez de
     adicionar uma dependência nova só pra isso, o botão "Baixar PDF" aciona a impressão
     nativa do navegador (window.print), que já oferece "Salvar como PDF". O CSS abaixo
     esconde o chrome do app (menu, filtro, rodapé) e mantém só o conteúdo do relatório. -->
<style>
    @media print {
        header, aside#sidebar, #botao-sidebar, footer, .no-print {
            display: none !important;
        }
        #area-conteudo {
            margin-left: 0 !important;
            overflow: visible !important;
        }
        body, .card-padrao {
            background: white !important;
            box-shadow: none !important;
        }
        #cabecalho-impressao {
            display: block !important;
        }
    }
</style>

<div class="max-w-2xl mx-auto pb-16 px-4 sm:px-6">
    <!-- Cabeçalho só visível na impressão/PDF, com contexto que some ao imprimir -->
    <div id="cabecalho-impressao" class="hidden mb-6">
        <h1 class="font-shantell text-2xl font-bold text-text-dark">Relatório — <?= htmlspecialchars($nomeEntidade) ?></h1>
        <p class="text-sm text-text-muted">Período: <?= htmlspecialchars($periodoRotulo) ?> • Gerado em <?= date('d/m/Y \à\s H:i') ?></p>
    </div>

    <div class="flex items-center justify-between pt-6 mb-6 no-print">
        <div>
            <h1 class="font-shantell text-2xl font-bold text-text-dark dark:text-white">Relatórios</h1>
            <p class="text-xs text-text-muted">Volume de animais e perfil de adoções da sua página</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button type="button" onclick="window.print()" class="text-xs font-bold text-white bg-primary px-3 py-2 rounded-full hover:opacity-90 transition">
                📄 Baixar PDF
            </button>
            <a href="<?= $urlBase ?>/perfil" class="text-xs font-bold text-primary dark:text-roxinhoFofo underline hover:opacity-80">
                &larr; Voltar
            </a>
        </div>
    </div>

    <!-- Filtro por mês/ano -->
    <form method="GET" action="<?= $urlBase ?>/relatorios" class="card-padrao flex flex-wrap items-end gap-3 mb-8 no-print">
        <div>
            <label class="label-padrao" for="filtro-ano">Ano</label>
            <select name="ano" id="filtro-ano" class="input-padrao">
                <option value="">Todos</option>
                <?php foreach ($anosDisponiveis as $ano): ?>
                    <option value="<?= $ano ?>" <?= $anoFiltro === $ano ? 'selected' : '' ?>><?= $ano ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-padrao" for="filtro-mes">Mês</label>
            <select name="mes" id="filtro-mes" class="input-padrao">
                <option value="">Todos</option>
                <?php foreach ($mesesNomes as $numero => $nome): ?>
                    <option value="<?= $numero ?>" <?= $mesFiltro === $numero ? 'selected' : '' ?>><?= $nome ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primario h-[42px]">Filtrar</button>
        <?php if ($mesFiltro !== null || $anoFiltro !== null): ?>
            <a href="<?= $urlBase ?>/relatorios" class="text-xs font-bold text-text-muted underline hover:text-text-dark dark:hover:text-white">Limpar filtro</a>
        <?php endif; ?>
    </form>

    <!-- Volume de Animais -->
    <section class="mb-8">
        <h2 class="font-shantell text-lg font-bold text-text-dark dark:text-white mb-3">Volume de Animais</h2>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="card-padrao text-center bg-roxo2 text-white">
                <p class="text-3xl font-bold"><?= (int) $relatorio['total_animais'] ?></p>
                <p class="text-xs font-poppins opacity-90 mt-1">Total de Animais</p>
            </div>
            <div class="card-padrao text-center bg-sucesso/15 border border-sucesso/30">
                <p class="text-3xl font-bold text-sucesso"><?= (int) $relatorio['total_adotados'] ?></p>
                <p class="text-xs font-poppins text-text-muted mt-1">Adoções Concluídas</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach ($relatorio['cards_status'] as $card): ?>
                <div class="card-padrao text-center">
                    <span class="text-2xl block mb-1"><?= $iconesStatus[$card['status']] ?? '📦' ?></span>
                    <p class="text-2xl font-bold text-text-dark dark:text-white"><?= (int) $card['total'] ?></p>
                    <p class="text-xs font-poppins text-text-muted mt-1"><?= htmlspecialchars($card['rotulo']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Perfil de Adoções -->
    <section class="mb-8">
        <h2 class="font-shantell text-lg font-bold text-text-dark dark:text-white mb-3">Perfil de Adoções</h2>

        <div class="card-padrao mb-4">
            <p class="text-xs font-poppins text-text-muted mb-1">Tempo médio até a adoção</p>
            <p class="text-2xl font-bold text-text-dark dark:text-white">
                <?php if ($relatorio['tempo_medio_dias'] !== null): ?>
                    <?= number_format($relatorio['tempo_medio_dias'], 1, ',', '.') ?> dias
                <?php else: ?>
                    <span class="text-base text-text-muted font-normal">Sem dados suficientes ainda</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <!-- Espécies mais adotadas -->
            <div class="card-padrao">
                <h3 class="text-sm font-bold text-text-dark dark:text-white mb-2">Espécie</h3>
                <?php if (empty($relatorio['especies_adotadas'])): ?>
                    <p class="text-xs text-text-muted italic">Sem adoções no período.</p>
                <?php else: ?>
                    <ul class="space-y-1.5">
                        <?php foreach ($relatorio['especies_adotadas'] as $linha): ?>
                            <li class="flex justify-between text-sm">
                                <span class="text-text-dark dark:text-white"><?= htmlspecialchars($linha['rotulo']) ?></span>
                                <span class="font-bold text-primary dark:text-roxinhoFofo"><?= (int) $linha['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Portes mais adotados -->
            <div class="card-padrao">
                <h3 class="text-sm font-bold text-text-dark dark:text-white mb-2">Porte</h3>
                <?php if (empty($relatorio['portes_adotados'])): ?>
                    <p class="text-xs text-text-muted italic">Sem adoções no período.</p>
                <?php else: ?>
                    <ul class="space-y-1.5">
                        <?php foreach ($relatorio['portes_adotados'] as $linha): ?>
                            <li class="flex justify-between text-sm">
                                <span class="text-text-dark dark:text-white"><?= htmlspecialchars($linha['rotulo']) ?></span>
                                <span class="font-bold text-primary dark:text-roxinhoFofo"><?= (int) $linha['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Faixa etária mais adotada -->
            <div class="card-padrao">
                <h3 class="text-sm font-bold text-text-dark dark:text-white mb-2">Idade</h3>
                <?php if (empty($relatorio['faixas_etarias'])): ?>
                    <p class="text-xs text-text-muted italic">Sem adoções no período.</p>
                <?php else: ?>
                    <ul class="space-y-1.5">
                        <?php foreach ($relatorio['faixas_etarias'] as $linha): ?>
                            <li class="flex justify-between text-sm gap-2">
                                <span class="text-text-dark dark:text-white"><?= htmlspecialchars($linha['rotulo']) ?></span>
                                <span class="font-bold text-primary dark:text-roxinhoFofo shrink-0"><?= (int) $linha['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
