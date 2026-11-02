import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initAdminNavigation } from '../../resources/js/admin-navigation.js';

function setup() {
    const node = () => ({
        listeners: {},
        addEventListener(event, listener) { this.listeners[event] = listener; },
        fire(event, details = {}) { this.listeners[event]?.(details); },
    });
    const trigger = { ...node(), focus() {}, setAttribute(name, value) { this[name] = value; } };
    const dialog = {
        ...node(), open: false,
        showModal() { this.open = true; },
        close() { this.open = false; this.fire('close'); },
        getBoundingClientRect() { return { left: 0, right: 320, top: 0, bottom: 844 }; },
    };
    const closeButton = node();
    const desktop = node();
    const nodes = { 'admin-mobile-navigation': dialog, 'admin-menu-toggle': trigger, 'admin-menu-close': closeButton };
    initAdminNavigation({ getElementById: id => nodes[id] }, desktop);
    return { trigger, dialog, closeButton, desktop };
}

test('menu button opens navigation and exposes expanded state', () => {
    const { trigger, dialog } = setup();
    trigger.fire('click');
    assert.equal(dialog.open, true);
    assert.equal(trigger['aria-expanded'], 'true');
    trigger.fire('click');
    assert.equal(dialog.open, true);
});

test('close button and native dialog close reset expanded state', () => {
    const { trigger, dialog, closeButton } = setup();
    trigger.fire('click');
    closeButton.fire('click');
    assert.equal(dialog.open, false);
    assert.equal(trigger['aria-expanded'], 'false');
    trigger.fire('click');
    dialog.close();
    assert.equal(trigger['aria-expanded'], 'false');
});

test('backdrop closes navigation while clicks inside do not', () => {
    const { trigger, dialog } = setup();
    trigger.fire('click');
    dialog.fire('click', { target: dialog, clientX: 100, clientY: 100 });
    assert.equal(dialog.open, true);
    dialog.fire('click', { target: dialog, clientX: 350, clientY: 100 });
    assert.equal(dialog.open, false);
});

test('switching to desktop closes the mobile dialog', () => {
    const { trigger, dialog, desktop } = setup();
    trigger.fire('click');
    desktop.fire('change', { matches: false });
    assert.equal(dialog.open, true);
    desktop.fire('change', { matches: true });
    assert.equal(dialog.open, false);
    assert.equal(trigger['aria-expanded'], 'false');
});

test('Customer pages without admin navigation initialize safely', () => {
    assert.doesNotThrow(() => initAdminNavigation({ getElementById: () => null }, {}));
});
