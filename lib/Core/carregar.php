<?php
/**
 * calendario :: carrega o nucleo. Web (config.php), CLI (cli/bootstrap.php) e testes incluem so isto.
 */
require_once __DIR__ . '/Erros.php';
require_once __DIR__ . '/CalErro.php';
require_once __DIR__ . '/Resultado.php';
require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/Log.php';
require_once __DIR__ . '/Auditoria.php';
require_once __DIR__ . '/Validar.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Permissao.php';
require_once __DIR__ . '/Credenciais.php';
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/../Feriados.php';

if (!defined('CAL_DIR_DADOS')) {
    define('CAL_DIR_DADOS', '/opt/mk-auth/dados/calendario');
}
if (!defined('CAL_DIR_LOGS')) {
    define('CAL_DIR_LOGS', '/opt/mk-auth/log/calendario');
}

/** Versao declarada no manifest.json. */
function cal_versao(): string
{
    static $v = null;
    if ($v === null) {
        $m = @json_decode((string) @file_get_contents(__DIR__ . '/../../manifest.json'), true);
        $v = isset($m['version']) ? (string) $m['version'] : '0';
    }
    return $v;
}
