<?php
/**
 * calendario :: regras da tabela compartilhada tab_feriados.
 *
 * Todos os addons leem a mesma tabela: dia nao util = sabado, domingo ou tipo = 'feriado'.
 * Este addon e o dono da tela de manutencao; as regras de gravacao ficam aqui:
 *   - feriado marcado a mao NUNCA e sobrescrito por uma sincronizacao (BrasilAPI);
 *   - toda alteracao guarda quem e quando (alterado_por/alterado_em) e vai para a auditoria.
 */
final class Feriados
{
    public const ABRANGENCIAS = ['nacional', 'estadual', 'municipal', 'outro'];
    public const ROTULOS = ['nacional' => 'Nacional', 'estadual' => 'Estadual', 'municipal' => 'Municipal', 'outro' => 'Outro'];

    private const COLUNAS = 'data, nome, tipo, abrangencia, origem, alterado_por, alterado_em';

    /** Feriados (e dias marcados) de um ano. */
    public static function doAno(int $ano): array
    {
        return Db::todos('SELECT ' . self::COLUNAS . ' FROM tab_feriados WHERE data >= ? AND data <= ? ORDER BY data',
            [sprintf('%04d-01-01', $ano), sprintf('%04d-12-31', $ano)]);
    }

    /** Proximos feriados a partir de hoje (relogio do banco). */
    public static function proximos(int $n = 8): array
    {
        return Db::todos("SELECT " . self::COLUNAS . " FROM tab_feriados WHERE tipo = 'feriado' AND data >= CURDATE()
                           ORDER BY data LIMIT " . max(1, min(50, $n)));
    }

    public static function obter(string $data): ?array
    {
        return Db::um('SELECT ' . self::COLUNAS . ' FROM tab_feriados WHERE data = ?', [$data]);
    }

    /**
     * Marca (ou altera) um feriado. Devolve [antes, depois] para a auditoria.
     * @return array{0:?array,1:array}
     */
    public static function salvar(string $data, string $nome, string $abrangencia, string $usuario, string $origem = 'manual'): array
    {
        $data = Validar::data($data);
        $nome = Validar::texto($nome, 150, true);
        $abrangencia = Validar::umDe($abrangencia, self::ABRANGENCIAS, 'municipal');
        $antes = self::obter($data);
        Db::exec("INSERT INTO tab_feriados (data, nome, tipo, abrangencia, origem, alterado_por, alterado_em)
                  VALUES (?, ?, 'feriado', ?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE nome = VALUES(nome), tipo = 'feriado', abrangencia = VALUES(abrangencia),
                                          origem = VALUES(origem), alterado_por = VALUES(alterado_por), alterado_em = NOW()",
            [$data, $nome, $abrangencia, substr($origem, 0, 20), substr($usuario, 0, 60)]);
        return [$antes, self::obter($data)];
    }

    /** Remove o dia (volta a ser dia util). Devolve o que existia. */
    public static function remover(string $data): ?array
    {
        $data = Validar::data($data);
        $antes = self::obter($data);
        Db::exec('DELETE FROM tab_feriados WHERE data = ?', [$data]);
        return $antes;
    }

    /**
     * Importa uma lista no formato da BrasilAPI ([{date, name, type}]). Dia que ja existe com
     * outra origem (marcado a mao, por outro addon...) e PRESERVADO; o que veio da propria
     * BrasilAPI antes e atualizado.
     *
     * @return array{novos:int,atualizados:int,preservados:int,ignorados:int}
     */
    public static function importar(array $lista, string $usuario, string $origem = 'brasilapi'): array
    {
        if (count($lista) > 200) {
            throw new CalErro('CAL-VAL-013');
        }
        $r = ['novos' => 0, 'atualizados' => 0, 'preservados' => 0, 'ignorados' => 0];
        Db::transacao(function () use ($lista, $usuario, $origem, &$r) {
            foreach ($lista as $f) {
                $data = is_array($f) ? (string) ($f['date'] ?? '') : '';
                $nome = is_array($f) ? trim((string) ($f['name'] ?? '')) : '';
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $nome === '') {
                    $r['ignorados']++;
                    continue;
                }
                $existe = self::obter($data);
                if ($existe !== null && $existe['origem'] !== $origem) {
                    $r['preservados']++;
                    continue;
                }
                self::salvar($data, $nome, 'nacional', $usuario, $origem);
                $r[$existe === null ? 'novos' : 'atualizados']++;
            }
        });
        return $r;
    }

    /** Busca na BrasilAPI pelo SERVIDOR (reserva para quando o navegador nao consegue). */
    public static function buscarBrasilApi(int $ano): array
    {
        $ano = Validar::inteiro($ano, 2000, 2100);
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: calendario-mkauth\r\n"]]);
        $json = @file_get_contents('https://brasilapi.com.br/api/feriados/v1/' . $ano, false, $ctx);
        $lista = $json !== false ? json_decode($json, true) : null;
        if (!is_array($lista)) {
            throw new CalErro('CAL-FER-001', [], null, 502);
        }
        return $lista;
    }

    /**
     * Endereco do botao "Voltar" vindo de outro addon. So aceita caminho RELATIVO para dentro
     * de outro addon do mesmo painel ("../livro_caixa/conferencia.php?x=1"); qualquer outra
     * coisa (http://, //, javascript:, ../../) vira vazio.
     */
    public static function urlVoltar(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 200) {
            return '';
        }
        if (!preg_match('#^\.\./[a-z0-9_]+/[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $url)) {
            return '';
        }
        return $url;
    }
}
