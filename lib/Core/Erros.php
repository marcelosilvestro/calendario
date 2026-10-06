<?php
/**
 * calendario :: catalogo de codigos de erro estaveis.
 *
 * O codigo e contrato: interface, logs e testes dependem dele, nunca do texto.
 */
final class Erros
{
    public const MENSAGENS = [
        'CAL-AUTH-001' => 'Sessão expirada.',
        'CAL-AUTH-002' => 'Você não tem permissão para esta operação.',
        'CAL-AUTH-003' => 'Requisição inválida (token de segurança).',
        'CAL-AUTH-004' => 'O administrador do addon já foi definido.',
        'CAL-AUTH-005' => 'O addon precisa ter ao menos um administrador.',
        'CAL-AUTH-006' => 'Login não encontrado entre os usuários do MK-AUTH.',

        'CAL-VAL-002' => 'Data inválida (use AAAA-MM-DD).',
        'CAL-VAL-005' => 'Login inválido.',
        'CAL-VAL-007' => 'Valor numérico fora da faixa permitida.',
        'CAL-VAL-008' => 'Informe o nome do feriado.',
        'CAL-VAL-010' => 'Opção inválida.',
        'CAL-VAL-013' => 'Lista de feriados inválida.',

        'CAL-CFG-001' => 'Configuração desconhecida.',
        'CAL-CFG-002' => 'Esta configuração é interna e não pode ser alterada pela interface.',

        'CAL-FER-001' => 'Não consegui buscar os feriados na BrasilAPI pelo servidor (sem acesso à internet?).',

        'CAL-SYS-001' => 'Erro interno. A ocorrência foi registrada no log do addon.',
        'CAL-SYS-002' => 'Valor inválido.',
        'CAL-SYS-003' => 'Operação desconhecida.',
        'CAL-SYS-004' => 'Método HTTP não permitido para esta operação.',
        'CAL-SYS-005' => 'O addon não encontrou a configuração de acesso ao banco. Rode o instalador.',
        'CAL-SYS-006' => 'O banco do addon não está instalado. Rode o instalador.',
        'CAL-SYS-007' => 'A consulta demorou demais e foi interrompida.',
    ];

    public static function mensagem(string $code): string
    {
        return self::MENSAGENS[$code] ?? 'Erro.';
    }

    public static function existe(string $code): bool
    {
        return isset(self::MENSAGENS[$code]);
    }
}
