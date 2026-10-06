<?php
/**
 * calendario :: tabela de operacoes AJAX.
 *
 * perm:
 *   'logado'  qualquer usuario do painel (ver o calendario e assumir o PRIMEIRO admin)
 *   outro     um papel de Permissao::PAPEIS (admin sempre passa)
 */
final class Rotas
{
    public const MAPA = [
        'inicio.estado'           => ['GET',  'logado', ['AjaxFeriado', 'estado']],
        'feriado.ano'             => ['GET',  'logado', ['AjaxFeriado', 'ano']],
        'feriado.salvar'          => ['POST', 'editar', ['AjaxFeriado', 'salvar']],
        'feriado.remover'         => ['POST', 'editar', ['AjaxFeriado', 'remover']],
        'feriado.importar'        => ['POST', 'editar', ['AjaxFeriado', 'importar']],
        'feriado.buscar_servidor' => ['GET',  'editar', ['AjaxFeriado', 'buscarServidor']],

        'permissao.assumir_admin' => ['POST', 'logado', ['AjaxPermissao', 'assumirAdmin']],
        'permissao.listar'        => ['GET',  'admin',  ['AjaxPermissao', 'listar']],
        'permissao.definir'       => ['POST', 'admin',  ['AjaxPermissao', 'definir']],
    ];
}
