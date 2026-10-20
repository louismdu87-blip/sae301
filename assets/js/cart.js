/* Les modifications de quantité sont validées par le bouton natif Mettre à jour le panier. */
jQuery(function ($) {
    function syncButtons() {
        document.querySelectorAll('.cart-quantity').forEach(function (control) {
            const input = control.querySelector('input.qty');
            if (!input) return;
            control.querySelector('[data-cart-step="-1"]').disabled = Number(input.value) <= Number(input.min || 0);
            control.querySelector('[data-cart-step="1"]').disabled = input.max !== '' && Number(input.value) >= Number(input.max);
        });
    }
    $(document.body).on('click', '[data-cart-step]', function () {
        const input = this.closest('.cart-quantity').querySelector('input.qty');
        if (this.dataset.cartStep === '1') input.stepUp();
        else input.stepDown();
        $(input).trigger('change');
    });
    $(document.body).on('input change', '.cart-quantity input.qty', syncButtons);
    $(document.body).on('updated_wc_div', syncButtons);
    // WooCommerce actualise le formulaire et les totaux ; les suggestions suivent la même réponse.
    $(document).on('ajaxSuccess', function (_event, xhr) {
        if (typeof xhr.responseText !== 'string' || !xhr.responseText.includes('product-recommendations--cart')) return;
        const response = $($.parseHTML(xhr.responseText, document, false));
        const next = response.find('.product-recommendations--cart').first();
        if (next.length) $('.product-recommendations--cart').replaceWith(next);
    });
    syncButtons();
});
