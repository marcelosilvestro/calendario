<?php
/**
 * Suite 01 :: schema e o contrato compartilhado tab_feriados.
 *
 * Caso real: a tabela ja existe, criada pelo Livro Caixa no formato antigo (data, nome, tipo),
 * com dados. O baseline tem de acrescentar as colunas novas SEM perder nenhuma linha.
 */
T::suite('Schema e contrato tab_feriados');

Db::pdo()->exec("CREATE TABLE tab_feriados (
    data DATE NOT NULL, nome VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    tipo ENUM('feriado','dia_util') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'feriado', PRIMARY KEY (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
Db::exec("INSERT INTO tab_feriados (data, nome, tipo) VALUES ('2026-09-07', 'Independência do Brasil', 'feriado'), ('2026-04-03', 'Sexta-feira Santa', 'feriado')");
Db::tabelaExiste(Db::ESQUECER);

$schema = new Schema(__DIR__ . '/../../sql');
$r = $schema->aplicar('teste', cal_versao());
T::igual('baseline cria/ajusta todas as tabelas', count(Schema::TABELAS), $r['tabelas']);
$schema->aplicar('teste', cal_versao());
T::certo('baseline e idempotente', $schema->estado()['instalado'] && !$schema->estado()['desatualizado']);
$cols = array_column(Db::todos("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tab_feriados'"), 'COLUMN_NAME');
T::igual('colunas novas acrescentadas', [], array_values(array_diff(['abrangencia', 'origem', 'alterado_por', 'alterado_em'], $cols)));
T::igual('nenhuma linha antiga perdida', 2, (int) Db::valor('SELECT COUNT(*) FROM tab_feriados'));
T::igual('linhas antigas ganham os padroes', ['nacional', 'manual'], array_values(Db::um("SELECT abrangencia, origem FROM tab_feriados WHERE data = '2026-09-07'")));
// O INSERT do Livro Caixa (so data, nome, tipo) continua funcionando sem mudanca
Db::exec("INSERT INTO tab_feriados (data, nome, tipo) VALUES ('2026-10-12', 'Nossa Senhora Aparecida', 'feriado') ON DUPLICATE KEY UPDATE nome = VALUES(nome)");
T::igual('INSERT antigo do Livro Caixa continua valido', 3, (int) Db::valor('SELECT COUNT(*) FROM tab_feriados'));

Db::pdo()->exec("CREATE TABLE sis_acesso (id INT AUTO_INCREMENT PRIMARY KEY, login VARCHAR(60), nome VARCHAR(255), ativo VARCHAR(3) DEFAULT 'sim')");
Db::exec("INSERT INTO sis_acesso (login, nome) VALUES ('teste', 'Teste'), ('operador', 'Operador')");
Db::tabelaExiste(Db::ESQUECER);
