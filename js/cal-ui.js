/*
 * calendario :: componentes de tela reutilizaveis.
 *
 * Vanilla + jQuery do core, sem framework (padrao do Livro Caixa). Toda chamada ao servidor
 * passa por CAL.api(): ela poe o token CSRF, trata sessao expirada e devolve sempre o
 * envelope { ok, data, errors, request_id }.
 */
var CAL = (function ($) {
    'use strict';

    var URL_LOGIN = '/admin/';

    function csrf() {
        var m = document.querySelector('meta[name="cal-csrf"]');
        return m ? m.getAttribute('content') : '';
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function pareceLogin(texto) {
        return typeof texto === 'string' &&
            (texto.indexOf('Acesso negado') !== -1 || texto.indexOf('login.hhvm') !== -1);
    }

    /**
     * Chama ajax.php?acao=...  Devolve uma Promise que resolve com `data` e rejeita com
     * { mensagem, codigo, resp }. A mensagem ja vem pronta para mostrar ao usuario.
     */
    function api(acao, dados, metodo) {
        metodo = metodo || 'GET';
        if (dados && Object.prototype.hasOwnProperty.call(dados, 'acao')) {
            return Promise.reject({ mensagem: 'Erro interno da tela: parâmetro "acao" reservado (' + acao + ').', codigo: null, resp: null });
        }
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: 'ajax.php?acao=' + encodeURIComponent(acao),
                method: metodo,
                data: dados || {},
                dataType: 'text',
                headers: metodo === 'POST' ? { 'X-CSRF-Token': csrf() } : {}
            }).always(function (a, status) {
                var texto = (status === 'success') ? a : (a && a.responseText);
                if (pareceLogin(texto)) { window.location.href = URL_LOGIN; return; }
                var resp;
                try { resp = JSON.parse(texto); } catch (e) {
                    var trecho = String(texto || '').replace(/<[^>]*>/g, ' ').trim().slice(0, 300);
                    reject({ mensagem: 'Resposta inesperada do servidor.' + (trecho ? ' ' + trecho : ''), codigo: null, resp: null });
                    return;
                }
                if (resp.sessao_expirada) { window.location.href = URL_LOGIN; return; }
                if (resp.ok) { resolve(resp.data); return; }
                var err = (resp.errors && resp.errors[0]) || {};
                reject({ mensagem: (err.message || 'Falha na operação.') + (resp.request_id ? ' (' + resp.request_id + ')' : ''),
                         codigo: err.code || null, detalhes: err.details || {}, resp: resp });
            });
        });
    }

    // ------------------------------------------------------------ loading e toast
    function loading(texto) {
        $('#cal-loading-texto').text(texto || 'Carregando...');
        $('#cal-loading').addClass('ativo');
    }
    function fimLoading() { $('#cal-loading').removeClass('ativo'); }

    /** tipo: ok | info | avis | erro */
    function toast(tipo, texto, sub) {
        var icones = { ok: 'bi-check-circle-fill', info: 'bi-info-circle-fill',
                       avis: 'bi-exclamation-triangle-fill', erro: 'bi-x-octagon-fill' };
        var $t = $('<div class="cal-toast ' + esc(tipo) + '" role="status"><i class="bi ' + (icones[tipo] || icones.info) + '"></i><div>' +
                   esc(texto) + (sub ? '<small>' + esc(sub) + '</small>' : '') + '</div></div>');
        $t.on('click', function () { $t.remove(); });
        $('#cal-toasts').append($t);
        setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, tipo === 'erro' ? 9000 : 4500);
    }

    function erro(e) { toast('erro', (e && e.mensagem) || String(e)); }

    // ------------------------------------------------------------ modais
    function abrirModal(id)  { $('#' + id).addClass('ativo'); }
    function fecharModal(id) { $('#' + id).removeClass('ativo'); }
    // Modal NAO fecha com clique fora (decisao de 02/10 nos addons): so pelo X ou pela acao.
    function fecharSeFora(ev, id) { }

    // ------------------------------------------------------------ formatacao
    function data(iso) {
        if (!iso) return '—';
        var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? m[3] + '/' + m[2] + '/' + m[1] : esc(iso);
    }
    function dataHora(iso) {
        if (!iso) return '—';
        var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return m ? m[3] + '/' + m[2] + '/' + m[1] + ' ' + m[4] + ':' + m[5] : esc(iso);
    }

    return {
        api: api, esc: esc, loading: loading, fimLoading: fimLoading, toast: toast, erro: erro,
        abrirModal: abrirModal, fecharModal: fecharModal, fecharSeFora: fecharSeFora, data: data, dataHora: dataHora
    };
})(jQuery);
