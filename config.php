<?php
/**
 * calendario :: bootstrap web do addon.
 *
 * Ordem obrigatoria (addon-mkauth-anatomia):
 *   config.php -> (ajax.php responde e sai) -> nav/header.php -> ../../topo.php
 *
 * Addon AUTOSSUFICIENTE: nada de outro addon e incluido. Do core do MK-AUTH vem apenas
 * topo.php, baixo.php, menu.js e scripts/jquery.js.
 *
 * SOMENTE LEITURA nas tabelas do MK-AUTH: o addon so escreve nas proprias tab_cal_* e na tabela
 * COMPARTILHADA tab_feriados, cujo contrato esta em sql/baseline.sql.
 */
include('addons.class.php');

$cal_ajax = defined('CAL_AJAX');

// ---------------------------------------------------------------- sessao do painel
if (!file_exists(__DIR__ . '/../../login.hhvm')) {
    $ext_mk = '.php';
    session_name('mka');
    if (!isset($_SESSION)) session_start();
    if (!isset($_SESSION['mka_logado'])) {
        if ($cal_ajax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false, 'data' => null, 'warnings' => [],
                'errors' => [['code' => 'CAL-AUTH-001', 'message' => 'Sessão expirada.', 'details' => []]],
                'sessao_expirada' => true,
            ]);
            exit;
        }
        exit('Acesso negado. <a href="/admin/login.php">Fazer Login</a>');
    }
} else {
    $ext_mk = '.hhvm';
    // Sessao gerenciada pelo MK-AUTH em modo HHVM
}

require_once __DIR__ . '/lib/Core/carregar.php';

// ---------------------------------------------------------------- banco
$CAL_DB = Credenciais::descobrir();
if ($CAL_DB === null) {
    if ($cal_ajax) {
        Resultado::erro('CAL-SYS-005')->enviar(500);
    }
    exit(htmlspecialchars(Credenciais::comoResolver()));
}

// mysqli exigido pelo topo.php do MK-AUTH
$link = @mysqli_connect($CAL_DB['host'], $CAL_DB['user'], $CAL_DB['pass'], $CAL_DB['name'], $CAL_DB['port']);
if (!$link) {
    exit('Falha na conexao com o banco de dados MySQL.');
}

try {
    $pdo = Db::conectar($CAL_DB);
} catch (PDOException $e) {
    Log::excecao('config.conectar', $e);
    if ($cal_ajax) {
        Resultado::erro('CAL-SYS-001')->enviar(500);
    }
    exit('Erro de conexao com o banco de dados.');
}
unset($CAL_DB);

$usuario_logado = (string) ($_SESSION['MKA_Usuario'] ?? $_SESSION['MM_Usuario'] ?? 'sistema');

Log::configurar(CAL_DIR_LOGS, $usuario_logado);
Auditoria::configurar($usuario_logado, $_SERVER['REMOTE_ADDR'] ?? null);
Permissao::configurar($usuario_logado);

// O schema minimo para qualquer tela: sem ele, nem a permissao pode ser checada.
$cal_schema_ok = Db::tabelaExiste('tab_cal_permissao') && Db::tabelaExiste('tab_cal_config')
             && Db::tabelaExiste('tab_feriados');
if (!$cal_schema_ok && $cal_ajax) {
    Resultado::erro('CAL-SYS-006')->enviar(500);
}
Db::limitarTempo(15);

// ---------------------------------------------------------------- CSRF
if (empty($_SESSION['cal_csrf'])) {
    $_SESSION['cal_csrf'] = bin2hex(random_bytes(16));
}
$cal_csrf = (string) $_SESSION['cal_csrf'];

// AJAX so LE a sessao daqui em diante: uma consulta lenta nao prende o usuario no painel.
if ($cal_ajax && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

/** Escapa para HTML. */
function cal_h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
