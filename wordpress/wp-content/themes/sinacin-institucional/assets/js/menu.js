document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('.sinacin-menu-toggle');
    const menu = document.querySelector('.sinacin-navigation');

    if (!button || !menu) return;

    button.addEventListener('click', function () {
        const isOpen = menu.classList.toggle('is-open');

        button.setAttribute('aria-expanded', String(isOpen));
        button.setAttribute(
            'aria-label',
            isOpen ? 'Cerrar menú' : 'Abrir menú'
        );
    });
});
