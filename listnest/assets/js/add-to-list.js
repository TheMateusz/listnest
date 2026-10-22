document.addEventListener('DOMContentLoaded', function() {
    if (typeof ListNestConfig === 'undefined' || !ListNestConfig.lists) {
        console.error('ListNestConfig nie jest zdefiniowane. Sprawdź wp_localize_script.');
        return;
    }
    const button = document.querySelector('.listnest-add-button');
    if (!button) return;

    jQuery(document).on('found_variation', 'form.variations_form', function(event, variation) {
        updateProductAttributes(variation);
    });

    jQuery(document).on('reset_data', 'form.variations_form', function() {
        updateProductAttributes(null);
    });

    function updateProductAttributes(variation) {
        let selectedAttributes = {};
        if (variation) {
            Object.keys(variation.attributes).forEach(key => {
                selectedAttributes[key] = variation.attributes[key];
            });

            button.setAttribute('data-product-id', variation.variation_id);
        } else {
            button.setAttribute('data-product-id', button.dataset.originalProductId);
        }

        button.setAttribute('data-product-attrs', JSON.stringify(selectedAttributes));
    }

    button.dataset.originalProductId = button.dataset.productId;

    document.addEventListener('click', function(e) {
        let btn = e.target.closest('.listnest-add-button');
        if (!btn) return;

        e.preventDefault();

        btn.disabled = true;
        btn.classList.add('listnest-loading');

        const listnestType  = btn.dataset.listnestType;
        const productId = parseInt(btn.dataset.productId) || 0;
        const productAttrs = btn.dataset.productAttrs ? JSON.parse(btn.dataset.productAttrs) : {};

        if (!listnestType || !productId) {
            alert('Brak wymaganych danych.');
            resetButton(btn);
            return;
        }

        const listConfig = ListNestConfig.lists[listnestType];
        if (!listConfig) {
            console.error(`Nie znaleziono konfiguracji dla: ${listnestType}`, ListNestConfig.lists);
            alert('Błąd konfiguracji listy.');
            resetButton(btn);
            return;
        }

        const postData = new URLSearchParams();
        postData.append('action', 'listnest_add_to_list');
        postData.append('security', ListNestConfig.nonce);
        postData.append('list_key', listnestType);
        postData.append('product_id', productId);
        postData.append('attributes', JSON.stringify(productAttrs));

        fetch(ListNestConfig.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            credentials: 'same-origin',
            body: postData
        })
            .then(response => response.ok ? response.json() : Promise.reject(`Błąd: ${response.status}`))
            .then(data => {
                if (data.success) {
                    if (listConfig.option === 'redirect' && listConfig.redirect_url) {
                        window.location.href = listConfig.redirect_url;
                    } else if (listConfig.option === 'popup') {
                        showListNestPopup(listConfig.popup);
                    }
                } else {
                    alert(data.data?.message || 'Błąd AJAX.');
                }
            })
            .catch(err => console.error('Fetch error:', err))
            .finally(() => {
                resetButton(btn);
            });
    });

    function resetButton(btn) {
        btn.disabled = false;
        btn.classList.remove('listnest-loading');
    }

    function showListNestPopup(popupData) {
        const popup = document.getElementById('listnest-popup-modal');
        if (!popup) {
            console.error('Nie znaleziono szablonu popupu.');
            return;
        }

        document.getElementById('listnest-popup-title').textContent = popupData.title || 'Informacja';

        const btn1 = document.getElementById('listnest-popup-btn1');
        if (popupData.btn1Text) {
            btn1.textContent = popupData.btn1Text;
            btn1.href = popupData.btn1Url || '#';
            btn1.style.display = 'inline-block';
        } else {
            btn1.style.display = 'none';
        }

        const btn2 = document.getElementById('listnest-popup-btn2');
        if (popupData.btn2Text) {
            btn2.textContent = popupData.btn2Text;
            btn2.href = popupData.btn2Url || '#';
            btn2.style.display = 'inline-block';
        } else {
            btn2.style.display = 'none';
        }

        popup.classList.add('show');

        document.querySelector('.listnest-popup-close').addEventListener('click', (e) => {
            e.preventDefault();
            popup.classList.remove('show');
        })
    }

});
