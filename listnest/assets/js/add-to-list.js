/**
 * ListNest – "Add to list" buttons and confirmation dialog.
 *
 * Vanilla JS. jQuery is used only to listen to WooCommerce variation events
 * (show_variation, hide_variation, reset_data), which are jQuery events.
 *
 * Every .listnest-add-button works independently: buttons inside a
 * WooCommerce product form read the selected variation, attributes and
 * quantity from that form; other buttons use their data attributes only.
 *
 * Dispatches a bubbling "listnest:added" CustomEvent on the button after a
 * successful request (detail: listKey, productId, variationId, quantity, response).
 */
(function () {
    'use strict';

    var config = window.ListNestConfig;
    if (!config || !config.lists) {
        return;
    }

    var i18n = config.i18n || {};
    var BUTTON_SELECTOR = '.listnest-add-button';
    var DISABLED_CLASS = 'listnest-is-disabled';
    var LOADING_CLASS = 'listnest-loading';

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    function getFeedbackElement(button) {
        var wrap = button.closest('.listnest-button-wrap');
        var el = wrap ? wrap.querySelector('.listnest-feedback') : null;

        if (!el) {
            el = document.createElement('span');
            el.className = 'listnest-feedback';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            button.insertAdjacentElement('afterend', el);
        }
        return el;
    }

    function showFeedback(button, message, type) {
        var el = getFeedbackElement(button);
        el.classList.remove('listnest-feedback--success', 'listnest-feedback--error');
        if (type) {
            el.classList.add('listnest-feedback--' + type);
        }
        el.textContent = message || '';
    }

    function clearFeedback(button) {
        var wrap = button.closest('.listnest-button-wrap');
        var el = wrap ? wrap.querySelector('.listnest-feedback') : null;
        if (el) {
            el.textContent = '';
            el.classList.remove('listnest-feedback--success', 'listnest-feedback--error');
        }
    }

    function setLoading(button, loading) {
        button.classList.toggle(LOADING_CLASS, loading);
        if (loading) {
            button.setAttribute('aria-busy', 'true');
        } else {
            button.removeAttribute('aria-busy');
        }
    }

    /**
     * Enables or disables (aria-disabled, so the button stays focusable and
     * can explain why it is disabled) every button inside a variations form.
     */
    function setFormButtonsEnabled(form, enabled) {
        if (!form) {
            return;
        }
        form.querySelectorAll(BUTTON_SELECTOR).forEach(function (button) {
            button.classList.toggle(DISABLED_CLASS, !enabled);
            if (enabled) {
                button.removeAttribute('aria-disabled');
                clearFeedback(button);
            } else {
                button.setAttribute('aria-disabled', 'true');
            }
        });
    }

    function getSelectedVariationId(form) {
        var input = form.querySelector('input[name="variation_id"]');
        return input ? parseInt(input.value, 10) || 0 : 0;
    }

    function getSelectedAttributes(form) {
        var attributes = {};
        form.querySelectorAll('[name^="attribute_"]').forEach(function (field) {
            if ((field.type === 'radio' || field.type === 'checkbox') && !field.checked) {
                return;
            }
            if (field.value) {
                attributes[field.name] = field.value;
            }
        });
        return attributes;
    }

    function getQuantity(button) {
        var form = button.closest('form');
        var input = form ? form.querySelector('input.qty') : null;
        var quantity = input ? parseInt(input.value, 10) : parseInt(button.getAttribute('data-quantity'), 10);
        return quantity > 0 ? quantity : 1;
    }

    function syncVariationForms() {
        document.querySelectorAll('form.variations_form').forEach(function (form) {
            if (form.querySelector(BUTTON_SELECTOR)) {
                setFormButtonsEnabled(form, getSelectedVariationId(form) > 0);
            }
        });
    }

    /* ------------------------------------------------------------------
     * Dialog
     * ------------------------------------------------------------------ */

    var Popup = (function () {
        var root = null;
        var dialog = null;
        var lastTrigger = null;
        var FOCUSABLE = 'a[href]:not([hidden]), button:not([disabled]):not([hidden]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

        function isOpen() {
            return root !== null && !root.hidden;
        }

        function focusableElements() {
            return Array.prototype.filter.call(dialog.querySelectorAll(FOCUSABLE), function (el) {
                return el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0;
            });
        }

        function close() {
            if (!isOpen()) {
                return;
            }
            root.hidden = true;
            root.classList.remove('is-open');
            document.documentElement.classList.remove('listnest-popup-open');

            if (lastTrigger && document.body.contains(lastTrigger)) {
                lastTrigger.focus();
            }
            lastTrigger = null;
        }

        function onKeydown(event) {
            if (!isOpen()) {
                return;
            }

            if (event.key === 'Escape' || event.key === 'Esc') {
                event.preventDefault();
                close();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            // Keep keyboard focus inside the dialog.
            var focusable = focusableElements();
            if (!focusable.length) {
                event.preventDefault();
                dialog.focus();
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function init() {
            root = document.getElementById('listnest-popup');
            if (!root) {
                return;
            }
            dialog = root.querySelector('[role="dialog"]');

            // Listeners are attached once; the dialog is reused for every list.
            root.addEventListener('click', function (event) {
                if (event.target.closest('[data-listnest-close]')) {
                    event.preventDefault();
                    close();
                }
            });
            document.addEventListener('keydown', onKeydown);
        }

        function open(data, trigger) {
            if (!root) {
                return false;
            }
            data = data || {};

            var title = root.querySelector('#listnest-popup-title');
            var secondary = root.querySelector('#listnest-popup-btn1');
            var primary = root.querySelector('#listnest-popup-btn2');

            if (title && data.title) {
                title.textContent = data.title;
            }
            if (secondary && data.btn1Text) {
                secondary.textContent = data.btn1Text;
            }
            if (primary) {
                if (data.btn2Url) {
                    primary.textContent = data.btn2Text || primary.textContent;
                    primary.setAttribute('href', data.btn2Url);
                    primary.hidden = false;
                } else {
                    primary.hidden = true;
                }
            }

            lastTrigger = trigger || document.activeElement;
            root.hidden = false;
            root.classList.add('is-open');
            document.documentElement.classList.add('listnest-popup-open');

            var target = (primary && !primary.hidden) ? primary : (secondary || dialog);
            target.focus();
            return true;
        }

        return { init: init, open: open, close: close };
    })();

    /* ------------------------------------------------------------------
     * Success behaviours
     * ------------------------------------------------------------------ */

    /**
     * "change_text": the button becomes a link to the configured page with
     * the configured text. Without a page, only the text changes.
     */
    function changeButton(button, listConfig, message) {
        var text = listConfig.buttonText || '';

        if (!listConfig.buttonUrl) {
            if (text) {
                button.textContent = text;
            }
            showFeedback(button, message, 'success');
            return;
        }

        var link = document.createElement('a');
        link.href = listConfig.buttonUrl;
        link.className = button.className;
        link.classList.remove('listnest-add-button', DISABLED_CLASS, LOADING_CLASS);
        link.classList.add('listnest-view-list');
        link.textContent = text || button.textContent;

        var hadFocus = document.activeElement === button;
        button.parentNode.replaceChild(link, button);
        showFeedback(link, message, 'success');
        if (hadFocus) {
            link.focus();
        }
    }

    function handleSuccess(button, listKey, listConfig, response) {
        var message = (response && response.message) || i18n.added || '';

        switch (listConfig.option) {
            case 'redirect':
                if (listConfig.redirectUrl) {
                    window.location.href = listConfig.redirectUrl;
                    return;
                }
                showFeedback(button, message, 'success');
                break;

            case 'popup':
                clearFeedback(button);
                if (!Popup.open(listConfig.popup, button)) {
                    showFeedback(button, message, 'success');
                }
                break;

            case 'change_text':
                changeButton(button, listConfig, message);
                break;

            default:
                showFeedback(button, message, 'success');
        }
    }

    /* ------------------------------------------------------------------
     * Requests
     * ------------------------------------------------------------------ */

    /**
     * POSTs form data to admin-ajax.php.
     * Resolves with { status: HTTP status, result: parsed JSON response }.
     */
    function postForm(body) {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            credentials: 'same-origin',
            body: body
        }).then(function (response) {
            return response.json()
                .catch(function () {
                    return { success: false, data: { message: i18n.error } };
                })
                .then(function (result) {
                    return { status: response.status, result: result };
                });
        });
    }

    function isNonceError(result) {
        return !!(result && result.success === false && result.data && result.data.code === 'invalid_nonce');
    }

    /**
     * Fetches a fresh nonce and stores it in the config.
     * Resolves with true on success, false otherwise.
     */
    function refreshNonce() {
        var body = new URLSearchParams();
        body.append('action', config.nonceRefreshAction || 'listnest_refresh_nonce');

        return postForm(body)
            .then(function (res) {
                var result = res.result;
                if (result && result.success && result.data && result.data.nonce) {
                    config.nonce = result.data.nonce;
                    return true;
                }
                return false;
            })
            .catch(function () {
                return false;
            });
    }

    /* ------------------------------------------------------------------
     * Click handler
     * ------------------------------------------------------------------ */

    function onButtonClick(event) {
        var button = event.target.closest(BUTTON_SELECTOR);
        if (!button || button.tagName !== 'BUTTON') {
            return;
        }
        event.preventDefault();

        if (button.classList.contains(LOADING_CLASS)) {
            return;
        }

        var listKey = button.getAttribute('data-listnest-type') || '';
        var productId = parseInt(button.getAttribute('data-product-id'), 10) || 0;
        var listConfig = config.lists[listKey];

        if (!listKey || !productId || !listConfig) {
            showFeedback(button, i18n.unknownList || i18n.error, 'error');
            return;
        }

        var variationId = parseInt(button.getAttribute('data-variation-id'), 10) || 0;
        var attributes = {};
        var variationsForm = button.closest('form.variations_form');

        if (variationsForm) {
            variationId = getSelectedVariationId(variationsForm);
            if (!variationId) {
                showFeedback(button, i18n.selectOptions, 'error');
                return;
            }
            attributes = getSelectedAttributes(variationsForm);
        }

        var quantity = getQuantity(button);

        function buildBody() {
            var body = new URLSearchParams();
            body.append('action', config.action || 'listnest_add_to_list');
            body.append('security', config.nonce);
            body.append('list_key', listKey);
            body.append('product_id', String(productId));
            if (variationId) {
                body.append('variation_id', String(variationId));
            }
            body.append('quantity', String(quantity));
            body.append('attributes', JSON.stringify(attributes));
            return body;
        }

        setLoading(button, true);
        showFeedback(button, i18n.adding || '', '');

        postForm(buildBody())
            .then(function (res) {
                // Cached pages may carry a stale nonce: refresh it once and retry once.
                if (res.status === 403 && isNonceError(res.result)) {
                    return refreshNonce().then(function (refreshed) {
                        return refreshed ? postForm(buildBody()) : res;
                    });
                }
                return res;
            })
            .then(function (res) {
                var result = res.result;
                setLoading(button, false);

                if (!result || !result.success) {
                    var message = result && result.data && result.data.message ? result.data.message : i18n.error;
                    showFeedback(button, message, 'error');
                    return;
                }

                button.dispatchEvent(new CustomEvent('listnest:added', {
                    bubbles: true,
                    detail: {
                        listKey: listKey,
                        productId: productId,
                        variationId: variationId,
                        quantity: quantity,
                        response: result.data
                    }
                }));

                handleSuccess(button, listKey, listConfig, result.data);
            })
            .catch(function () {
                setLoading(button, false);
                showFeedback(button, i18n.networkError || i18n.error, 'error');
            });
    }

    /* ------------------------------------------------------------------
     * WooCommerce variation events (jQuery events), bound immediately so
     * events fired during WooCommerce's own initialisation are not missed.
     * ------------------------------------------------------------------ */

    if (window.jQuery) {
        window.jQuery(document)
            .on('show_variation', 'form.variations_form', function () {
                setFormButtonsEnabled(this, true);
            })
            .on('hide_variation reset_data', 'form.variations_form', function () {
                setFormButtonsEnabled(this, false);
            });
    }

    function init() {
        Popup.init();
        syncVariationForms();
        document.addEventListener('click', onButtonClick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
