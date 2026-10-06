<?php
/**
 * calendario :: elementos fixos de toda pagina — loading e toasts.
 *
 * Fica DEPOIS do baixo.php, como filho direto do <body>: dentro do wrapper do addon, um
 * stacking context prende o modal abaixo do #sistema-rodape e o botao para de responder
 * ao clique (addon-mkauth-anatomia).
 */
?>
<div class="lc-loading-overlay" id="cal-loading">
    <div class="lc-loading-box">
        <div class="lc-loading-spinner"></div>
        <div class="lc-loading-text" id="cal-loading-texto">Carregando...</div>
    </div>
</div>

<div id="cal-toasts" class="cal-toasts"></div>

<div style="position: fixed; bottom: 10px; right: 15px; font-size: 11px; color: #9ca3af; z-index: 9999; font-family: 'Inter', sans-serif; pointer-events: none;">
    By Marcelo Silvestro
</div>
