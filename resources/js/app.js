

import Alpine from 'alpinejs';
import { copyBookingCode } from './booking-code';
import { initAdminNavigation } from './admin-navigation';

window.Alpine = Alpine;

Alpine.start();
initAdminNavigation();

document.getElementById('copy-booking-code')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    document.getElementById('booking-copy-status').textContent = await copyBookingCode(
        document.getElementById('booking-code').textContent.trim(),
    );
    button.disabled = false;
});
