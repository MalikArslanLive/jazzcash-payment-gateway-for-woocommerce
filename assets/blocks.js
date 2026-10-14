(function () {
    'use strict';
    const decode = window.wp.htmlEntities.decodeEntities;
    const el = window.wp.element.createElement;
    ['jccg', 'jccg_wallet'].forEach(function (name) {
        const data = window.wc.wcSettings.getSetting(name + '_data', null);
        if (!data) { return; }
        const title = decode(data.title || 'JazzCash');
        const content = el('p', null, decode(data.description || ''));
        window.wc.wcBlocksRegistry.registerPaymentMethod({
            name: name, label: el('span', null, title), ariaLabel: title,
            content: content, edit: content,
            canMakePayment: () => Boolean(data.enabled),
            supports: { features: data.supports || ['products'] }
        });
    });
}());
