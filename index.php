<?php
require_once __DIR__ . '/config.php';
// Quem chegou de outro addon (Livro Caixa, Painel Financeiro) ganha o botao "Voltar".
$cal_voltar = $cal_schema_ok ? Feriados::urlVoltar($_GET['voltar'] ?? '') : '';
include('nav/header.php');
?>
<body data-ext="<?= cal_h($ext_mk) ?>">
<?php include('../../topo.php'); ?>

<div class="container-fluid px-3 py-0 cal-wrap">
<?php if (!$cal_schema_ok): ?>
    <div class="cal-aviso erro"><i class="bi bi-database-exclamation"></i>
        <div><strong>O banco do addon não está instalado.</strong> Rode o instalador no terminal do servidor (como root).</div></div>
<?php else: ?>

    <div class="cal-topo">
        <div class="cal-topo-tit">
            <h1><i class="bi bi-calendar-x"></i> Calendário de Feriados</h1>
            <span>Sábados e domingos já são folga bancária. Este calendário é compartilhado pelos addons (Livro Caixa, Painel Financeiro).</span>
        </div>
        <div class="cal-ano" role="group" aria-label="Ano">
            <button type="button" id="ano-ant" aria-label="Ano anterior">&#10094;</button>
            <span id="ano-rot">Ano</span>
            <button type="button" id="ano-prox" aria-label="Próximo ano">&#10095;</button>
        </div>
        <div class="cal-topo-acoes">
            <button type="button" class="lc-btn-black" id="btn-sync" style="display:none"><i class="bi bi-cloud-download"></i> Sincronizar nacionais</button>
            <button type="button" class="lc-btn-outline" id="btn-perm" style="display:none"><i class="bi bi-people"></i> Permissões</button>
            <?php if ($cal_voltar !== ''): ?>
            <a class="lc-btn-outline" href="<?= cal_h($cal_voltar) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="cal-sem-admin" class="cal-aviso info" style="display:none">
        <i class="bi bi-person-badge"></i>
        <div style="flex:1"><strong>Este addon ainda não tem administrador.</strong> O administrador decide quem pode marcar e remover feriados.</div>
        <button type="button" class="lc-btn-black" id="btn-assumir"><i class="bi bi-shield-check"></i> Assumir administração</button>
    </div>

    <div class="cal-corpo">
        <div class="cal-meses" id="cal-meses" aria-live="polite"><div class="lc-loading">Carregando...</div></div>
        <aside class="cal-lado">
            <div class="lc-section-panel" style="height:auto">
                <div class="lc-section-header"><span class="lc-section-title"><i class="bi bi-calendar-event"></i> Próximos feriados</span></div>
                <ul class="cal-prox" id="cal-prox"><li class="lc-loading">Carregando...</li></ul>
            </div>
            <div class="lc-section-panel" style="height:auto">
                <div class="lc-section-header"><span class="lc-section-title"><i class="bi bi-info-circle"></i> Legenda</span><span class="cal-sub" id="cal-tot"></span></div>
                <ul class="cal-legenda">
                    <li><span class="cal-dia nacional">1</span> Feriado nacional</li>
                    <li><span class="cal-dia estadual">1</span> Feriado estadual</li>
                    <li><span class="cal-dia municipal">1</span> Feriado municipal</li>
                    <li><span class="cal-dia outro">1</span> Outro (recesso, ponto facultativo)</li>
                    <li><span class="cal-dia fds">1</span> Sábado ou domingo</li>
                    <li><span class="cal-dia hoje">1</span> Hoje</li>
                </ul>
                <div class="cal-nota" id="cal-nota-editar"></div>
            </div>
        </aside>
    </div>

<?php endif; ?>
</div>

<?php include('../../baixo.php'); ?>
<?php include('nav/comum.php'); ?>

<div class="lc-overlay" id="modal-dia">
    <div class="lc-modal-box sm">
        <div class="lc-modal-header">
            <span class="lc-modal-title" id="md-titulo">Dia</span>
            <button type="button" class="lc-modal-close" aria-label="Fechar" onclick="CAL.fecharModal('modal-dia')">&times;</button>
        </div>
        <div class="lc-modal-body">
            <div id="md-info" class="cal-sub" style="margin-bottom:10px"></div>
            <div id="md-form">
                <label class="lc-label" for="md-nome">Nome ou motivo</label>
                <input type="text" class="lc-input" id="md-nome" maxlength="150" placeholder="Ex.: Aniversário da cidade">
                <label class="lc-label cal-mt" for="md-abr">Abrangência</label>
                <select class="lc-input" id="md-abr">
                    <option value="municipal">Municipal</option><option value="estadual">Estadual</option>
                    <option value="nacional">Nacional</option><option value="outro">Outro (recesso, ponto facultativo)</option>
                </select>
            </div>
        </div>
        <div class="lc-modal-footer" style="justify-content:space-between">
            <button type="button" class="lc-btn-outline" id="md-remover" style="color:#c0392b;display:none"><i class="bi bi-trash"></i> Tornar dia útil</button>
            <span style="display:flex;gap:8px;margin-left:auto">
                <button type="button" class="lc-btn-cancel" onclick="CAL.fecharModal('modal-dia')">Fechar</button>
                <button type="button" class="lc-btn-confirm" id="md-salvar">Marcar como feriado</button>
            </span>
        </div>
    </div>
</div>

<div class="lc-overlay" id="modal-perm">
    <div class="lc-modal-box lg">
        <div class="lc-modal-header">
            <span class="lc-modal-title">Permissões</span>
            <button type="button" class="lc-modal-close" aria-label="Fechar" onclick="CAL.fecharModal('modal-perm')">&times;</button>
        </div>
        <div class="lc-modal-body">
            <div class="lc-table-wrap"><table class="lc-table"><thead><tr><th>Login</th><th>Papéis</th><th></th></tr></thead><tbody id="perm-linhas"></tbody></table></div>
            <label class="lc-label cal-mt" for="perm-login">Conceder ou alterar acesso</label>
            <select class="lc-input" id="perm-login"></select>
            <div class="cal-papeis-grid cal-mt" id="perm-papeis"></div>
        </div>
        <div class="lc-modal-footer">
            <button type="button" class="lc-btn-cancel" onclick="CAL.fecharModal('modal-perm')">Fechar</button>
            <button type="button" class="lc-btn-confirm" id="perm-salvar">Salvar</button>
        </div>
    </div>
</div>

<script src="../../menu.js<?= $ext_mk ?>"></script>
<script>
(function () {
    var MESES = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    var SEMANA = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    var ROT = { nacional: 'Nacional', estadual: 'Estadual', municipal: 'Municipal', outro: 'Outro' };
    var EST = null, ANO = null, DIAS = {};
    var q = new URLSearchParams(window.location.search);

    function iso(a, m, d) { return a + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0'); }

    function desenhar() {
        var hoje = EST.hoje, html = '', tot = { nacional: 0, estadual: 0, municipal: 0, outro: 0 };
        Object.keys(DIAS).forEach(function (k) { if (DIAS[k].tipo === 'feriado') tot[DIAS[k].abrangencia] = (tot[DIAS[k].abrangencia] || 0) + 1; });
        for (var m = 1; m <= 12; m++) {
            var primeiro = new Date(ANO, m - 1, 1).getDay(), n = new Date(ANO, m, 0).getDate();
            html += '<section class="cal-mes" aria-label="' + MESES[m - 1] + '"><h2>' + MESES[m - 1] + '</h2><div class="cal-sem"><span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span></div><div class="cal-grade">';
            for (var i = 0; i < primeiro; i++) html += '<span class="cal-vazio"></span>';
            for (var d = 1; d <= n; d++) {
                var data = iso(ANO, m, d), dow = new Date(ANO, m - 1, d).getDay(), f = DIAS[data];
                var cls = ['cal-dia'];
                if (f && f.tipo === 'feriado') cls.push(f.abrangencia); else if (dow === 0 || dow === 6) cls.push('fds');
                if (data === hoje) cls.push('hoje');
                var rot = d + ' de ' + MESES[m - 1] + ', ' + SEMANA[dow] + (f ? ': ' + f.nome : '');
                html += '<button type="button" class="' + cls.join(' ') + '" data-d="' + data + '" title="' + CAL.esc(f ? f.nome : '') + '" aria-label="' + CAL.esc(rot) + '">' + d + '</button>';
            }
            html += '</div></section>';
        }
        $('#cal-meses').html(html);
        $('#cal-tot').text(ANO + ': ' + (tot.nacional + tot.estadual + tot.municipal + tot.outro) + ' dia(s)');
        $('#ano-rot').text('Ano ' + ANO);
    }

    function carregarAno(ano) {
        ANO = ano;
        $('#ano-rot').text('Ano ' + ANO);
        $('#cal-meses').html('<div class="lc-loading">Carregando...</div>');
        return CAL.api('feriado.ano', { ano: ano }).then(function (r) {
            DIAS = {};
            r.dias.forEach(function (x) { DIAS[x.data] = x; });
            desenhar();
            history.replaceState(null, '', 'index.php?ano=' + ano + (q.get('voltar') ? '&voltar=' + encodeURIComponent(q.get('voltar')) : ''));
        }).catch(CAL.erro);
    }

    function proximos() {
        CAL.api('inicio.estado').then(function (e) {
            EST = e;
            $('#cal-prox').html(e.proximos.length ? e.proximos.map(function (f) {
                var dt = new Date(f.data + 'T12:00:00');
                return '<li><span class="cal-dia ' + CAL.esc(f.abrangencia) + '">' + dt.getDate() + '</span><div><b>' + CAL.esc(f.nome) + '</b>' +
                       '<div class="cal-sub">' + CAL.data(f.data) + ' · ' + SEMANA[dt.getDay()] + ' · ' + CAL.esc(ROT[f.abrangencia] || f.abrangencia) + '</div></div></li>';
            }).join('') : '<li class="cal-sub">Nenhum feriado futuro cadastrado. Use "Sincronizar nacionais".</li>');
        });
    }

    function abrirDia(data) {
        var f = DIAS[data], dt = new Date(data + 'T12:00:00'), fds = dt.getDay() === 0 || dt.getDay() === 6;
        $('#md-titulo').text(CAL.data(data) + ' — ' + SEMANA[dt.getDay()]);
        $('#md-info').html(f ? 'Feriado <b>' + CAL.esc(ROT[f.abrangencia] || '') + '</b>' +
            (f.alterado_por ? ' · alterado por ' + CAL.esc(f.alterado_por) + ' em ' + CAL.dataHora(f.alterado_em) : '') +
            ' · origem: ' + CAL.esc(f.origem)
            : (fds ? 'Fim de semana: já é folga bancária. Marque só se precisar registrar o motivo.' : 'Dia útil.'));
        $('#md-nome').val(f ? f.nome : '');
        $('#md-abr').val(f ? f.abrangencia : 'municipal');
        $('#md-form :input').prop('disabled', !EST.editar);
        $('#md-salvar').toggle(EST.editar).text(f ? 'Salvar' : 'Marcar como feriado').off('click').on('click', function () { salvar(data); });
        $('#md-remover').toggle(!!f && EST.editar).off('click').on('click', function () { remover(data); });
        CAL.abrirModal('modal-dia');
        if (EST.editar) setTimeout(function () { $('#md-nome').trigger('focus'); }, 50);
    }

    function salvar(data) {
        var nome = $.trim($('#md-nome').val());
        if (!nome) { CAL.toast('avis', 'Informe o nome do feriado.'); return; }
        CAL.loading('Gravando...');
        CAL.api('feriado.salvar', { data: data, nome: nome, abrangencia: $('#md-abr').val() }, 'POST').then(function () {
            CAL.fecharModal('modal-dia'); CAL.toast('ok', 'Feriado gravado.'); carregarAno(ANO); proximos();
        }).catch(CAL.erro).finally(CAL.fimLoading);
    }

    function remover(data) {
        CAL.loading('Gravando...');
        CAL.api('feriado.remover', { data: data }, 'POST').then(function () {
            CAL.fecharModal('modal-dia'); CAL.toast('ok', CAL.data(data) + ' voltou a ser dia útil.'); carregarAno(ANO); proximos();
        }).catch(CAL.erro).finally(CAL.fimLoading);
    }

    /** Nacionais do ano exibido e do seguinte: pelo navegador; se falhar, pelo servidor. */
    function sincronizar() {
        var anos = [ANO, ANO + 1], soma = { novos: 0, atualizados: 0, preservados: 0 };
        CAL.loading('Buscando feriados nacionais de ' + anos.join(' e ') + '...');
        var buscar = function (a) {
            return fetch('https://brasilapi.com.br/api/feriados/v1/' + a)
                .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
                .catch(function () { return CAL.api('feriado.buscar_servidor', { ano: a }).then(function (x) { return x.lista; }); });
        };
        anos.reduce(function (p, a) {
            return p.then(function () { return buscar(a); }).then(function (lista) {
                return CAL.api('feriado.importar', { lista: JSON.stringify(lista) }, 'POST');
            }).then(function (r) { soma.novos += r.novos; soma.atualizados += r.atualizados; soma.preservados += r.preservados; });
        }, Promise.resolve()).then(function () {
            CAL.toast('ok', soma.novos + ' novo(s), ' + soma.atualizados + ' atualizado(s).',
                soma.preservados ? soma.preservados + ' dia(s) marcados à mão foram preservados.' : '');
            carregarAno(ANO); proximos();
        }).catch(CAL.erro).finally(CAL.fimLoading);
    }

    // ------------------------------------------------------------------ permissoes
    var PERM = null, ROT_PAPEL = { admin: 'Administrador', editar: 'Editar feriados' };
    function carregarPerm() {
        return CAL.api('permissao.listar').then(function (d) {
            PERM = d;
            $('#perm-linhas').html(d.permissoes.length ? d.permissoes.map(function (p) {
                return '<tr><td><b>' + CAL.esc(p.login) + '</b></td><td>' + p.papeis.map(function (x) {
                    return '<span class="cal-papel' + (x === 'admin' ? ' admin' : '') + '">' + CAL.esc(ROT_PAPEL[x] || x) + '</span>'; }).join('') +
                    '</td><td style="text-align:right"><button type="button" class="lc-btn-outline" data-login="' + CAL.esc(p.login) + '">Editar</button></td></tr>';
            }).join('') : '<tr><td colspan="3" class="lc-empty">Ninguém além de você.</td></tr>');
            var $s = $('#perm-login').empty().append('<option value="">Selecione o usuário...</option>');
            d.usuarios.forEach(function (u) { $s.append($('<option>').val(u.login).text(u.login + (u.nome ? ' — ' + u.nome : ''))); });
            marcarPapeis('');
        });
    }
    function marcarPapeis(login) {
        var atuais = [];
        (PERM.permissoes || []).forEach(function (p) { if (p.login === login) atuais = p.papeis; });
        $('#perm-papeis').html(PERM.papeis.map(function (p) {
            return '<label><input type="checkbox" value="' + CAL.esc(p.id) + '"' + (atuais.indexOf(p.id) !== -1 ? ' checked' : '') + '><span><strong>' +
                   CAL.esc(ROT_PAPEL[p.id] || p.id) + '</strong><small>' + CAL.esc(p.descricao) + '</small></span></label>';
        }).join(''));
    }

    function iniciar(e) {
        EST = e;
        $('#cal-sem-admin').toggle(!e.ha_admin);
        $('#btn-sync').toggle(e.editar);
        $('#btn-perm').toggle(e.admin);
        $('#cal-nota-editar').text(e.editar ? 'Clique num dia para marcar ou remover um feriado.' : 'Você pode consultar. Para marcar feriados, peça o papel "Editar feriados" ao administrador.');
        var ano = parseInt(q.get('ano'), 10);
        carregarAno(ano >= 2000 && ano <= 2100 ? ano : +e.hoje.slice(0, 4));
        proximos();
    }

    $(function () {
        CAL.api('inicio.estado').then(iniciar).catch(CAL.erro);
        $('#ano-ant').on('click', function () { carregarAno(ANO - 1); });
        $('#ano-prox').on('click', function () { carregarAno(ANO + 1); });
        $('#cal-meses').on('click', '.cal-dia', function () { abrirDia($(this).attr('data-d')); });
        $('#btn-sync').on('click', sincronizar);
        $('#btn-perm').on('click', function () { carregarPerm().then(function () { CAL.abrirModal('modal-perm'); }).catch(CAL.erro); });
        $('#perm-linhas').on('click', '[data-login]', function () { $('#perm-login').val($(this).attr('data-login')); marcarPapeis($(this).attr('data-login')); });
        $('#perm-login').on('change', function () { marcarPapeis($(this).val()); });
        $('#perm-salvar').on('click', function () {
            var login = $('#perm-login').val();
            if (!login) { CAL.toast('avis', 'Selecione o usuário.'); return; }
            CAL.api('permissao.definir', { login: login, papeis: $('#perm-papeis input:checked').map(function () { return this.value; }).get() }, 'POST')
                .then(function () { CAL.toast('ok', 'Permissões de ' + login + ' salvas.'); return carregarPerm(); }).catch(CAL.erro);
        });
        $('#btn-assumir').on('click', function () {
            CAL.api('permissao.assumir_admin', {}, 'POST').then(function () {
                CAL.toast('ok', 'Você agora é o administrador do calendário.');
                return CAL.api('inicio.estado').then(iniciar);
            }).catch(CAL.erro);
        });
    });
})();
</script>
</body>
</html>
