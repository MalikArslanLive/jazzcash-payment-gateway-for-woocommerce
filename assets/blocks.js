(function () {
    'use strict';
    const data = window.wc.wcSettings.getSetting('jccg_data', {});
    const decode = window.wp.htmlEntities.decodeEntities;
    const el = window.wp.element.createElement;
    const title = decode(data.title || 'JazzCash Credit / Debit Card');
    const content = el('p', null, decode(data.description || 'Pay securely by card on JazzCash.'));
    window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: 'jccg', label: el('span', null, title), ariaLabel: title,
        content: content, edit: content,
        canMakePayment: () => true,
        supports: { features: data.supports || ['products'] }
    });
}());
