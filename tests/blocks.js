'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync(require('path').join(__dirname, '../assets/blocks.js'), 'utf8');
function load(data) {
    const methods = [];
    vm.runInNewContext(source, {window: {
        wc: {wcSettings: {getSetting: (name, fallback) => data[name] || fallback},
            wcBlocksRegistry: {registerPaymentMethod: (method) => methods.push(method)}},
        wp: {htmlEntities: {decodeEntities: (value) => value},
            element: {createElement: (type, props, child) => ({type, props, child})}}
    }});
    return methods;
}
const methods = load({jccg_data: {title: 'Card', description: 'Card payment', enabled: true},
    jccg_wallet_data: {title: 'Wallet', description: '<script>bad()</script>', enabled: true}});
assert.strictEqual(methods.length, 2);
assert.strictEqual(methods[0].name, 'jccg');
assert.strictEqual(methods[1].name, 'jccg_wallet');
assert.strictEqual(methods[1].label.child, 'Wallet');
assert.strictEqual(methods[1].content.child, '<script>bad()</script>');
assert.strictEqual(methods[1].content.props, null);
assert.strictEqual(methods[0].canMakePayment(), true);
assert.strictEqual(methods[1].canMakePayment(), true);
assert.strictEqual(load({jccg_wallet_data: {enabled: false}})[0].canMakePayment(), false);
assert.strictEqual(load({}).length, 0);
console.log('PASS: 10 Blocks registration, availability and text-rendering assertions');
