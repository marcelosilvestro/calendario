# Changelog

## 0.1.0 — 06/10/2026

- Primeira versão: calendário anual de feriados compartilhado, a partir do calendário bancário do
  Livro Caixa.
- Contrato da tabela `tab_feriados` com as colunas `abrangencia`, `origem`, `alterado_por` e
  `alterado_em`, compatível com a tabela existente do Livro Caixa.
- Sincronização com a BrasilAPI pelo navegador e pelo servidor, preservando feriados manuais.
- Papéis (ver, editar, administrador), CSRF e auditoria.
