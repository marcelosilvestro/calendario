<?php
/**
 * calendario :: configuracao geral, chave/valor com tipo e faixa.
 *
 * Os padroes vivem AQUI, nao no banco: uma instalacao nova funciona sem seed, e uma
 * atualizacao que muda um padrao vale para quem nunca alterou aquele valor.
 * tab_cal_config guarda so o que o administrador mudou e os valores internos.
 */
require_once __DIR__ . '/Db.php';
require_once __DIR__ . '/Validar.php';
require_once __DIR__ . '/CalErro.php';

final class Config
{
    /**
     * tipo: bool | int | enum | texto.  Chaves com 'interno' => true nao aparecem nem sao
     * alteraveis pela interface. O calendario quase nao tem configuracao: o que importa e o
     * contrato da tabela tab_feriados.
     */
    public const DEFINICOES = [
        'admin_definido_em' => ['tipo' => 'texto', 'padrao' => '', 'interno' => true],
    ];

    private static array $cache = [];
    private static bool $carregado = false;

    public static function get(string $chave): string
    {
        if (!isset(self::DEFINICOES[$chave])) {
            throw new CalErro('CAL-CFG-001', ['chave' => $chave]);
        }
        self::carregar();
        $v = self::$cache[$chave] ?? null;
        return ($v === null || $v === '') ? (string) self::DEFINICOES[$chave]['padrao'] : (string) $v;
    }

    public static function int(string $chave): int
    {
        return (int) self::get($chave);
    }

    public static function ligado(string $chave): bool
    {
        return self::get($chave) === '1';
    }

    /**
     * Grava um valor validado. Devolve [antes, depois] para a auditoria de quem chamou.
     * @return array{0:string,1:string}
     */
    public static function set(string $chave, $valor, string $usuario, bool $permitirInterno = false): array
    {
        $def = self::DEFINICOES[$chave] ?? null;
        if ($def === null) {
            throw new CalErro('CAL-CFG-001', ['chave' => $chave]);
        }
        if (!empty($def['interno']) && !$permitirInterno) {
            throw new CalErro('CAL-CFG-002', ['chave' => $chave]);
        }
        $novo = self::normalizar($def, $valor);
        $antes = self::get($chave);

        Db::exec(
            'INSERT INTO tab_cal_config (chave, valor, alterado_por, alterado_em)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), alterado_por = VALUES(alterado_por), alterado_em = NOW()',
            [$chave, $novo, $usuario]
        );
        self::$cache[$chave] = $novo;
        return [$antes, $novo];
    }

    /** Tudo o que a tela de configuracoes mostra, agrupado, com o valor efetivo. */
    public static function paraTela(): array
    {
        $saida = [];
        foreach (self::DEFINICOES as $chave => $def) {
            if (!empty($def['interno'])) {
                continue;
            }
            $saida[] = [
                'chave'  => $chave,
                'grupo'  => $def['grupo'],
                'tipo'   => $def['tipo'],
                'rotulo' => $def['rotulo'],
                'ajuda'  => $def['ajuda'],
                'min'    => $def['min'] ?? null,
                'max'    => $def['max'] ?? null,
                'opcoes' => $def['opcoes'] ?? null,
                'padrao' => $def['padrao'],
                'valor'  => self::get($chave),
            ];
        }
        return $saida;
    }

    public static function limparCache(): void
    {
        self::$cache = [];
        self::$carregado = false;
    }

    private static function carregar(): void
    {
        if (self::$carregado) {
            return;
        }
        foreach (Db::todos('SELECT chave, valor FROM tab_cal_config') as $r) {
            self::$cache[$r['chave']] = $r['valor'];
        }
        self::$carregado = true;
    }

    private static function normalizar(array $def, $valor): string
    {
        switch ($def['tipo']) {
            case 'bool':
                return Validar::bool($valor) ? '1' : '0';
            case 'int':
                return (string) Validar::inteiro($valor, (int) $def['min'], (int) $def['max']);
            case 'faixas':
                return Validar::faixas($valor);
            case 'palavras':
                return Validar::listaPalavras($valor);
            case 'enum':
                if (!in_array((string) $valor, $def['opcoes'], true)) {
                    throw new CalErro('CAL-SYS-002', ['opcoes' => $def['opcoes']]);
                }
                return (string) $valor;
            default:
                return Validar::texto($valor, 255);
        }
    }
}
