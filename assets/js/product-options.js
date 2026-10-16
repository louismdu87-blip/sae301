/* Les boutons reflètent le sélecteur WooCommerce ; celui-ci gère les variations. */
jQuery(function ($) {
    $('.product-view--variable').each(function () {
        const product = this;
        const form = product.querySelector('form.variations_form');
        const groups = product.querySelectorAll('.product-options__formats');
        if (!form || !groups.length) return;
        let activeVariation = null;
        const priceSettings = window.buissierePriceSettings;

        function formatPrice(amount) {
            const parts = Number(amount).toFixed(priceSettings.decimals).split('.');
            const integer = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, priceSettings.thousandSeparator);
            const number = integer + (parts[1] ? priceSettings.decimalSeparator + parts[1] : '');
            return priceSettings.priceFormat
                .replace('%1$s', priceSettings.currencySymbol)
                .replace('%2$s', number);
        }

        function syncTotal() {
            if (!activeVariation || !priceSettings) return;
            const input = form.querySelector('input.qty');
            const amount = form.querySelector('.woocommerce-variation-price .woocommerce-Price-amount bdi');
            const meta = form.querySelector('.product-options__price-meta');
            if (!input || !amount) return;
            if (!activeVariation.is_in_stock) {
                amount.textContent = formatPrice(activeVariation.display_price);
                amount.setAttribute('aria-label', 'Prix unitaire');
                const select = form.querySelector('select[name="attribute_format"]');
                if (meta) meta.textContent = 'Prix unitaire TTC' + (select ? ' · ' + select.value : '');
                return;
            }
            const quantity = Number(input.value);
            if (!input.validity.valid || !Number.isFinite(quantity) || quantity <= 0) {
                amount.textContent = '—';
                if (meta) meta.textContent = 'Choisissez une quantité valide';
                return;
            }
            const unitPrice = Number(activeVariation.display_price);
            amount.textContent = formatPrice(unitPrice * quantity);
            amount.setAttribute('aria-label', 'Total pour ' + quantity + (quantity > 1 ? ' produits' : ' produit'));
            if (meta) {
                const select = form.querySelector('select[name="attribute_format"]');
                const format = select ? select.options[select.selectedIndex].text : '';
                meta.textContent = 'Total TTC' + (format ? ' · ' + format : '') + ' · ' + formatPrice(unitPrice) + ' / unité';
            }
        }


        function syncFormats() {
            groups.forEach(function (group) {
                const select = form.querySelector('select[name="' + group.dataset.attribute + '"]');
                if (!select) return;
                group.querySelectorAll('.product-options__radio').forEach(function (radio) {
                    const option = Array.from(select.options).find(function (item) {
                        return item.value === radio.value;
                    });
                    radio.checked = select.value === radio.value;
                    radio.disabled = !option || option.disabled;
                });
            });
        }

        function syncQuantity() {
            const input = form.querySelector('input.qty');
            if (!input) return;
            input.disabled = !activeVariation || !activeVariation.is_in_stock;
            const minus = form.querySelector('[data-quantity-step="-1"]');
            const plus = form.querySelector('[data-quantity-step="1"]');
            const value = Number(input.value);
            if (minus) minus.disabled = input.disabled || value <= Number(input.min || 1);
            if (plus) plus.disabled = input.disabled || (input.max !== '' && value >= Number(input.max));
            syncTotal();
        }

        groups.forEach(function (group) {
            group.addEventListener('change', function (event) {
                const radio = event.target;
                if (!radio.matches('.product-options__radio')) return;
                const select = form.querySelector('select[name="' + group.dataset.attribute + '"]');
                if (select) $(select).val(radio.value).trigger('change');
            });
            group.hidden = false;
        });

        $(form).on('change', 'select', syncFormats);
        $(form).on('woocommerce_update_variation_values', syncFormats);
        $(form).on('found_variation', function (event, variation) {
            activeVariation = variation;
            product.classList.add('product-view--selected');
            syncFormats();
            syncQuantity();
        });
        $(form).on('reset_data hide_variation', function () {
            activeVariation = null;
            product.classList.remove('product-view--selected');
            syncFormats();
            syncQuantity();
        });
        $(form).on('show_variation', syncQuantity);
        $(form).on('input change', 'input.qty', syncQuantity);
        form.querySelectorAll('[data-quantity-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = form.querySelector('input.qty');
                if (!input || input.disabled || input.readOnly) return;
                if (button.dataset.quantityStep === '1') input.stepUp();
                else input.stepDown();
                $(input).trigger('input').trigger('change');
            });
        });
        form.classList.add('product-options--enhanced');
        syncFormats();
        syncQuantity();
    });
});
