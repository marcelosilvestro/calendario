# Calendário de Feriados — addon para MK-AUTH

Um calendário só de **dias não úteis**, compartilhado por todos os addons do MK-AUTH: a
conferência bancária do Livro Caixa (compensação D+0/D+1), a agenda de cobrança do Painel
Financeiro (corte em feriado) e qualquer addon futuro.

- Grade anual com os 12 meses; clicar num dia marca ou remove o feriado (nome e abrangência:
  nacional, estadual, municipal ou outro).
- **Sincronizar nacionais**: busca na BrasilAPI os feriados do ano exibido e do seguinte. A busca
  é feita pelo navegador; se ele não conseguir, o servidor tenta. Um feriado marcado à mão nunca é
  sobrescrito.
- Papéis: qualquer usuário do painel **vê**; **Editar feriados** marca e remove; **Administrador**
  concede papéis. Toda alteração fica na auditoria.

## Instalação

```sh
wget -O - https://raw.githubusercontent.com/marcelosilvestro/calendario/main/instalar.sh | bash
```

Sem internet no servidor: `bash instalar.sh --pacote=calendario-X.Y.Z.tar.gz`.
O item aparece em **Opções › Calendário de Feriados**.

## Contrato da tabela `tab_feriados`

A tabela é **compartilhada**. Este addon é o dono da tela, mas qualquer addon pode ler e, com
cuidado, gravar. Quem não encontra a tabela cria com o **mesmo DDL**, que está em
[`sql/baseline.sql`](sql/baseline.sql).

| Coluna | Significado |
|---|---|
| `data` | o dia (chave) |
| `nome` | nome do feriado ou motivo |
| `tipo` | `feriado` = dia não útil (`dia_util` existe por compatibilidade, sem uso) |
| `abrangencia` | `nacional`, `estadual`, `municipal` ou `outro` |
| `origem` | quem gravou: `brasilapi`, `manual` ou o nome do addon que gravou |
| `alterado_por`, `alterado_em` | a última alteração |

**Regra de leitura em todos os addons:** dia não útil = sábado, domingo ou `tipo = 'feriado'`.

**Regras de escrita:** sempre preencher `origem`. Uma sincronização automática só altera linhas da
própria origem. As colunas novas têm valor padrão, então um `INSERT (data, nome, tipo)` antigo
continua válido.

## Integração com outros addons

Para abrir o calendário e voltar para a tela de origem:

```
../calendario/index.php?voltar=../livro_caixa/conferencia.php
```

O `voltar` só aceita caminho relativo para outro addon do mesmo painel.

## Desenvolvimento

```sh
bash empacotar.sh
php tests/run.php --conf=/opt/mk-auth/conf/calendario.php
```

## Licença

MIT.
