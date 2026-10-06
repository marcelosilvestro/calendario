<?php
/**
 * calendario :: diagnostico da instalacao (so le).
 *
 *   php cli/diagnostico.php [--conf=ARQ]
 */
require_once __DIR__ . '/bootstrap.php';

$args = cal_cli_args($argv);
cal_cli_conectar($args);

$ok = true;
$linha = function (bool $bom, string $txt) use (&$ok) {
    $ok = $ok && $bom;
    echo ($bom ? '  ok   ' : '  XX   ') . $txt . "\n";
};
echo "Calendario de Feriados " . cal_versao() . " :: diagnostico\n";
$est = (new Schema())->estado();
$linha($est['instalado'], $est['instalado'] ? 'tabelas presentes (inclusive a compartilhada tab_feriados)' : 'faltam tabelas: ' . implode(', ', $est['faltando']));
$linha(!$est['desatualizado'], $est['desatualizado'] ? 'schema desatualizado: rode o instalador' : 'schema em dia com o codigo');
if ($est['instalado']) {
    $cols = array_column(Db::todos("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tab_feriados'"), 'COLUMN_NAME');
    $faltam = array_diff(['data', 'nome', 'tipo', 'abrangencia', 'origem', 'alterado_por', 'alterado_em'], $cols);
    $linha(!$faltam, $faltam ? 'tab_feriados sem as colunas: ' . implode(', ', $faltam) : 'tab_feriados no contrato atual');
    $n = (int) Db::valor("SELECT COUNT(*) FROM tab_feriados WHERE tipo = 'feriado' AND data >= CURDATE()");
    echo "  ..   feriados futuros cadastrados: $n\n";
}
foreach ([CAL_DIR_LOGS] as $dir) {
    $linha(is_dir($dir) && is_writable($dir), "pasta $dir " . (is_dir($dir) ? (is_writable($dir) ? 'gravavel' : 'sem permissao de escrita') : 'nao existe'));
}
exit($ok ? 0 : 1);
