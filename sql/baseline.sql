-- calendario :: schema completo, idempotente.
--
-- Roda inteiro a cada instalacao/atualizacao (lib/Core/Schema.php): o que ja existe fica,
-- o que falta e criado.
--
-- Regras de escrita deste arquivo (o separador de comandos e simples):
--   * todo comando termina com ";" no FIM da linha
--   * comentario so em linha propria comecando com "--", nunca depois do ";"

CREATE TABLE IF NOT EXISTS `tab_cal_migration` (
  `migration`   VARCHAR(100) NOT NULL,
  `checksum`    CHAR(64)     NOT NULL,
  `versao`      VARCHAR(20)  NOT NULL DEFAULT '0',
  `executed_at` DATETIME     NOT NULL,
  `executed_by` VARCHAR(60)  NULL,
  `duracao_ms`  INT UNSIGNED NULL,
  `resultado`   VARCHAR(10)  NOT NULL DEFAULT 'ok',
  `erro`        TEXT         NULL,
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_cal_config` (
  `chave`        VARCHAR(64) NOT NULL,
  `valor`        TEXT        NULL,
  `alterado_por` VARCHAR(60) NULL,
  `alterado_em`  DATETIME    NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_cal_permissao` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login`      VARCHAR(60)  NOT NULL,
  `papel`      VARCHAR(40)  NOT NULL,
  `criado_por` VARCHAR(60)  NULL,
  `criado_em`  DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_login_papel` (`login`, `papel`),
  KEY `ix_papel` (`papel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tab_cal_auditoria` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `criado_em`   DATETIME        NOT NULL,
  `usuario`     VARCHAR(60)     NOT NULL,
  `ip`          VARCHAR(45)     NULL,
  `acao`        VARCHAR(60)     NOT NULL,
  `entidade`    VARCHAR(40)     NOT NULL,
  `entidade_id` BIGINT UNSIGNED NULL,
  `antes`       MEDIUMTEXT      NULL,
  `depois`      MEDIUMTEXT      NULL,
  `correlacao`  VARCHAR(64)     NULL,
  `request_id`  VARCHAR(32)     NULL,
  PRIMARY KEY (`id`),
  KEY `ix_criado` (`criado_em`),
  KEY `ix_entidade` (`entidade`, `entidade_id`),
  KEY `ix_usuario` (`usuario`),
  KEY `ix_correlacao` (`correlacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================================
-- CONTRATO COMPARTILHADO: tab_feriados
--
-- Uma tabela so para todos os addons (calendario, painel_financeiro, livro_caixa). Quem nao a
-- encontra cria com ESTE MESMO DDL — o bloco abaixo e copiado igual no baseline do
-- painel_financeiro e do livro_caixa. Mudou aqui, muda la.
--
--   data         dia (chave)
--   nome         nome do feriado ou motivo
--   tipo         'feriado' = dia nao util. ('dia_util' existe por compatibilidade, sem uso.)
--   abrangencia  nacional | estadual | municipal | outro
--   origem       quem gravou: brasilapi | manual | painel_financeiro | livro_caixa ...
--   alterado_*   ultima alteracao (vazio nas linhas antigas do Livro Caixa)
--
-- Regra de leitura em todos os addons: dia nao util = sabado, domingo ou tipo = 'feriado'.
-- As colunas novas tem padrao: os INSERTs antigos do Livro Caixa (data, nome, tipo) continuam
-- funcionando sem mudanca.
-- ============================================================================================
CREATE TABLE IF NOT EXISTS `tab_feriados` (
  `data` DATE         NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `tipo` ENUM('feriado','dia_util') NOT NULL DEFAULT 'feriado',
  PRIMARY KEY (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tab_feriados`
  ADD COLUMN IF NOT EXISTS `abrangencia` ENUM('nacional','estadual','municipal','outro') NOT NULL DEFAULT 'nacional',
  ADD COLUMN IF NOT EXISTS `origem` VARCHAR(20) NOT NULL DEFAULT 'manual',
  ADD COLUMN IF NOT EXISTS `alterado_por` VARCHAR(60) NULL,
  ADD COLUMN IF NOT EXISTS `alterado_em` DATETIME NULL;
