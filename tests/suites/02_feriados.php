<?php
/**
 * Suite 02 :: regras de gravacao dos feriados.
 */
T::suite('Feriados');

[$a, $d] = Feriados::salvar('2026-11-28', 'Aniversário da cidade', 'municipal', 'teste');
T::igual('marcar: nao existia', null, $a);
T::igual('marcar: grava abrangencia, origem e autor', ['municipal', 'manual', 'teste'], [$d['abrangencia'], $d['origem'], $d['alterado_por']]);
[$a, $d] = Feriados::salvar('2026-11-28', 'Aniversário do município', 'municipal', 'teste');
T::igual('alterar: devolve o antes para a auditoria', 'Aniversário da cidade', $a['nome']);
T::recusa('nome obrigatorio', fn() => Feriados::salvar('2026-11-29', '  ', 'municipal', 'teste'), 'CAL-VAL-008');
T::recusa('data invalida', fn() => Feriados::salvar('2026-02-30', 'X', 'municipal', 'teste'), 'CAL-VAL-002');
T::recusa('abrangencia fora da lista', fn() => Feriados::salvar('2026-11-29', 'X', 'galactica', 'teste'), 'CAL-VAL-010');
T::igual('ano: lista os dias do ano', 4, count(Feriados::doAno(2026)));
T::igual('remover devolve o que existia', 'Aniversário do município', Feriados::remover('2026-11-28')['nome']);
T::igual('remover dia que nao existe', null, Feriados::remover('2026-11-28'));

T::suite('Importacao (BrasilAPI)');

Db::exec("UPDATE tab_feriados SET origem = 'brasilapi' WHERE data = '2026-04-03'");
$r = Feriados::importar([
    ['date' => '2026-09-07', 'name' => 'Independência', 'type' => 'national'],      // existe, origem manual -> preserva
    ['date' => '2026-04-03', 'name' => 'Paixão de Cristo', 'type' => 'national'],   // existe, origem brasilapi -> atualiza
    ['date' => '2026-12-25', 'name' => 'Natal', 'type' => 'national'],              // novo
    ['date' => 'x', 'name' => 'Lixo'],                                              // ignorado
], 'teste');
T::igual('importar: novos, atualizados, preservados, ignorados', ['novos' => 1, 'atualizados' => 1, 'preservados' => 1, 'ignorados' => 1], $r);
T::igual('feriado marcado a mao nao e sobrescrito', 'Independência do Brasil', Feriados::obter('2026-09-07')['nome']);
T::igual('o que veio da propria BrasilAPI e atualizado', 'Paixão de Cristo', Feriados::obter('2026-04-03')['nome']);
T::igual('importado entra como nacional/brasilapi', ['nacional', 'brasilapi'], [Feriados::obter('2026-12-25')['abrangencia'], Feriados::obter('2026-12-25')['origem']]);
T::recusa('lista grande demais', fn() => Feriados::importar(array_fill(0, 201, []), 'teste'), 'CAL-VAL-013');

T::suite('Botao Voltar');

T::igual('caminho de outro addon', '../livro_caixa/conferencia.php', Feriados::urlVoltar('../livro_caixa/conferencia.php'));
T::igual('com parametros simples', '../painel_financeiro/agenda.php?mes=2026-10', Feriados::urlVoltar('../painel_financeiro/agenda.php?mes=2026-10'));
foreach (['http://mal.com/x.php', '//mal.com/x.php', 'javascript:alert(1)', '../../login.php', '../livro_caixa/../../x.php', '../x/y.php"><script>'] as $ruim) {
    T::igual("recusa $ruim", '', Feriados::urlVoltar($ruim));
}

T::suite('Permissoes');

Permissao::esquecer();
T::igual('sem papel nao edita', false, Permissao::tem('editar'));
Permissao::assumirAdmin('teste');
Permissao::definir('operador', ['editar'], 'teste');
T::igual('papel editar', true, Permissao::tem('editar', 'operador'));
T::igual('editar nao e admin', false, Permissao::tem('admin', 'operador'));
foreach (Rotas::MAPA as $acao => [$metodo, $perm]) {
    if ($metodo === 'POST' && $acao !== 'permissao.assumir_admin') {
        T::certo("escrita $acao exige papel", $perm !== 'logado');
    }
}
T::igual('ver o calendario e livre para quem esta logado', 'logado', Rotas::MAPA['feriado.ano'][1]);
