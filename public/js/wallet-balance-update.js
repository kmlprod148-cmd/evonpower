/**
 * Mise à jour du solde client après paiement par crédit
 * Écoute l'événement walletBalanceUpdated pour mettre à jour tous les affichages de solde
 * sur toutes les pages concernées (dashboard, navbar, dropdown, etc.)
 */
(function() {
    'use strict';
    function updateBalanceDisplays(e) {
        if (!e || !e.detail) return;
        var d = e.detail;
        var formatted = (d.formatted !== undefined) ? d.formatted :
            ((d.balance !== undefined) ? (parseFloat(d.balance).toFixed(2) + ' EUR') :
            ((d.remaining_balance !== undefined) ? (parseFloat(d.remaining_balance).toFixed(2) + ' EUR') : null));
        if (!formatted) return;
        document.querySelectorAll('[data-balance-value]').forEach(function(el) {
            el.textContent = formatted;
        });
        document.querySelectorAll('#user-balance-display, #user-balance-display-mobile, #available-balance, #current-balance, #user-balance').forEach(function(el) {
            if (el) el.textContent = formatted;
        });
    }
    document.addEventListener('walletBalanceUpdated', updateBalanceDisplays);
    window.addEventListener('walletBalanceUpdated', updateBalanceDisplays);
})();
