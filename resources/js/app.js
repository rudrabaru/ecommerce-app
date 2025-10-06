import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Global AJAX bootstrap for no-reload UX (re-init tables, modals, and validations)
(() => {
    // Fire this to re-initialize tables and bind events after AJAX navigation
    function triggerAjaxPageLoaded() {
        const evt = new Event('ajaxPageLoaded');
        window.dispatchEvent(evt);
    }

    // Helper that pages can call after they inject new HTML via AJAX
    window.AjaxBootstrap = {
        onNavigate: triggerAjaxPageLoaded,
        // Optionally expose a manual reinit hook
        reinit: triggerAjaxPageLoaded,
    };

    // If you use links with class .js-ajax-link to fetch and swap content, you can call AjaxBootstrap.onNavigate() after swap.
    // Example (pseudo): fetch(url).then(html => { container.innerHTML = html; AjaxBootstrap.onNavigate(); })
})();
