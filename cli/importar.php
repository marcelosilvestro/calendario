<?php
/**
 * calendario :: importa os feriados nacionais da BrasilAPI pelo servidor.
 *
 *   php cli/importar.php [--anos=2026,2027] [--se-vazio] [--conf=ARQ]
 *
 * --se-vazio: so importa quando tab_feriados nao tem nenhum dia (uso do instalador).
 * Feriado marcado a mao nunca e sobrescrito. Saida: 0 ok, 1 falha, 3 sem configuracao de banco.
 */
require_once __DIR__ . '/bootstrap.php';

$args = cal_cli_args($argv);
cal_cli_conectar($args);

if (isset($args['se-vazio']) && (int) Db::valor('SELECT COUNT(*) FROM tab_feriados') > 0) {
    echo "tab_feriados ja tem dias cadastrados; nada importado.\n";
    exit(0);
}
$hoje = (int) substr((string) Db::valor('SELECT CURDATE()'), 0, 4);
$anos = isset($args['anos']) && is_string($args['anos']) ? array_map('intval', explode(',', $args['anos'])) : [$hoje, $hoje + 1];
try {
    foreach ($anos as $ano) {
        $r = Feriados::importar(Feriados::buscarBrasilApi($ano), cal_usuario_cli());
        Auditoria::registrar('feriado_importar', 'feriado', null, null, $r + ['ano' => $ano, 'via' => 'cli']);
        printf("%d: %d novo(s), %d atualizado(s), %d preservado(s)\n", $ano, $r['novos'], $r['atualizados'], $r['preservados']);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERRO: ' . $e->getMessage() . "\n");
    exit(1);
}
exit(0);
