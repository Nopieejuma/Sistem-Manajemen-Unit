export function initAdminNavigation(root = document, desktop = window.matchMedia('(min-width: 768px)')) {
    const dialog = root.getElementById('admin-mobile-navigation');
    const trigger = root.getElementById('admin-menu-toggle');
    const closeButton = root.getElementById('admin-menu-close');
    if (!dialog || !trigger || !closeButton) {
        return;
    }
    trigger.addEventListener('click', () => {
        if (!dialog.open) {
            trigger.focus();
            dialog.showModal();
            trigger.setAttribute('aria-expanded', 'true');
        }
    });
    closeButton.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => trigger.setAttribute('aria-expanded', 'false'));
    dialog.addEventListener('click', (event) => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
            dialog.close();
        }
    });
    desktop.addEventListener('change', (event) => {
        if (event.matches && dialog.open) {
            dialog.close();
        }
    });
}
