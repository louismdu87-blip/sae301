/* Itération 6. */
(function () {
    const settings = window.buissierePickupInstructions;
    const checkout = window.wc && window.wc.blocksCheckout;
    if (!settings || !checkout || !checkout.ExperimentalOrderLocalPickupPackages) return;
    const createElement = window.wp.element.createElement;

    function PickupInstructions() {
        return createElement('section', {
            className: 'pickup-instructions',
            'aria-labelledby': 'pickup-instructions-title'
        },
        createElement('h3', { className: 'pickup-instructions__title', id: 'pickup-instructions-title' }, settings.title),
        createElement('p', { className: 'pickup-instructions__text' }, settings.text));
    }

    window.wp.plugins.registerPlugin('buissiere-pickup-instructions', {
        scope: 'woocommerce-checkout',
        render: function () {
            return createElement(checkout.ExperimentalOrderLocalPickupPackages, null,
                createElement(PickupInstructions));
        }
    });
})();
