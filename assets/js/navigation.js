/* Itération 2. */
(function () {
    const header = document.querySelector('.brand-header');
    if (!header) return;
    const toggle = header.querySelector('.brand-header__toggle');
    const navigation = header.querySelector('.brand-header__navigation');
    if (!toggle || !navigation) return;
    const icon = toggle.querySelector('path');
    const desktop = window.matchMedia('(min-width: 40rem)');

    function setOpen(open, returnFocus) {
        header.classList.toggle('brand-header--open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        icon.setAttribute('d', open ? 'M6 6l12 12M18 6 6 18' : 'M4 6h16M4 12h16M4 18h16');
        if (returnFocus && !desktop.matches) toggle.focus();
    }

    toggle.addEventListener('click', function () {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    navigation.addEventListener('click', function (event) {
        if (event.target.closest('a')) setOpen(false, true);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            event.preventDefault();
            setOpen(false, true);
        }
    });
    document.addEventListener('click', function (event) {
        if (!header.contains(event.target)) setOpen(false);
    });
    desktop.addEventListener('change', function () {
        setOpen(false, navigation.contains(document.activeElement));
    });
    header.classList.add('brand-header--enhanced');
    toggle.hidden = false;
})();
