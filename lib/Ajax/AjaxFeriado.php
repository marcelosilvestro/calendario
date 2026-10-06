<?php
/**
 * calendario :: operacoes da tela.
 */
final class AjaxFeriado
{
    public static function estado(array $e): array
    {
        return [
            'versao'   => cal_versao(),
            'hoje'     => (string) Db::valor('SELECT CURDATE()'),
            'ha_admin' => Permissao::haAdmin(),
            'editar'   => Permissao::tem('editar'),
            'admin'    => Permissao::tem('admin'),
            'proximos' => Feriados::proximos(8),
        ];
    }

    public static function ano(array $e): array
    {
        $ano = Validar::inteiro($e['ano'] ?? date('Y'), 2000, 2100);
        return ['ano' => $ano, 'dias' => Feriados::doAno($ano)];
    }

    public static function salvar(array $e): array
    {
        [$antes, $depois] = Feriados::salvar((string) ($e['data'] ?? ''), (string) ($e['nome'] ?? ''),
            (string) ($e['abrangencia'] ?? ''), Permissao::login());
        Auditoria::registrar($antes ? 'feriado_alterar' : 'feriado_marcar', 'feriado', null, $antes, $depois);
        return ['feriado' => $depois];
    }

    public static function remover(array $e): array
    {
        $antes = Feriados::remover((string) ($e['data'] ?? ''));
        if ($antes) {
            Auditoria::registrar('feriado_remover', 'feriado', null, $antes, null);
        }
        return ['removido' => $antes !== null];
    }

    /** Lista vinda do navegador (que buscou na BrasilAPI) em JSON. */
    public static function importar(array $e): array
    {
        $lista = json_decode((string) ($e['lista'] ?? ''), true);
        if (!is_array($lista)) {
            throw new CalErro('CAL-VAL-013');
        }
        $r = Feriados::importar($lista, Permissao::login());
        Auditoria::registrar('feriado_importar', 'feriado', null, null, $r + ['recebidos' => count($lista)]);
        return $r;
    }

    /** Reserva: o servidor busca na BrasilAPI quando o navegador nao conseguiu. */
    public static function buscarServidor(array $e): array
    {
        $ano = Validar::inteiro($e['ano'] ?? date('Y'), 2000, 2100);
        return ['lista' => Feriados::buscarBrasilApi($ano)];
    }
}
