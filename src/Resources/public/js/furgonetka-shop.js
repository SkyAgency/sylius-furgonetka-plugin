/**
 * Furgonetka pickup-point selection on the checkout shipping step.
 *
 * Adds a "choose pickup point" button to pickup-point shipping methods, opens the
 * Furgonetka map, and saves the chosen point against the cart. Shared by the
 * Sylius 1.14 and 2.x storefronts.
 */
(() => {
    'use strict';

    const SDK_SCRIPT_ID = 'furgonetka-map-sdk';

    // Page-wide checkout config (endpoints, labels, SDK URL, address) exposed as data-attributes.
    const readConfig = () => {
        const el = document.getElementById('furgonetka-checkout-config');
        return el ? el.dataset : {};
    };

    // Load the Furgonetka map SDK once; reuse it if it is already on the page.
    const loadSdk = (sdkUrl) => new Promise((resolve, reject) => {
        if (window.Furgonetka && typeof window.Furgonetka.Map === 'function') {
            resolve();
            return;
        }

        if (!sdkUrl) {
            reject(new Error('[furgonetka] Map SDK URL is not configured (missing data-sdk-url)'));
            return;
        }

        const existing = document.getElementById(SDK_SCRIPT_ID);
        if (existing) {
            existing.addEventListener('load', resolve);
            existing.addEventListener('error', reject);
            return;
        }

        const script = document.createElement('script');
        script.id = SDK_SCRIPT_ID;
        script.src = sdkUrl;
        script.async = true;
        script.addEventListener('load', resolve);
        script.addEventListener('error', reject);
        document.head.appendChild(script);
    });

    // Per-method map settings (courier service + allowed point types) from the method's data-attributes.
    const configFor = (container) => ({
        courierService: container.dataset.courierService || '',
        pointsTypes: (container.dataset.pointsTypes || '').split(',').filter(Boolean),
    });

    // Shipping address from the previous checkout step, used to centre the map.
    const readAddress = (config) => ({
        city: config.city || '',
        street: config.street || '',
        postcode: config.postcode || '',
        countryCode: config.countryCode || '',
    });

    // Save the chosen point against the current cart (one point per order).
    const savePoint = async (container, point, config) => {
        if (!config.saveUrl) {
            return;
        }

        const response = await fetch(config.saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': config.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                shippingMethodCode: container.dataset.methodCode,
                code: point.code,
                name: point.name,
                type: point.type,
            }),
        });

        if (!response.ok) {
            throw new Error(`[furgonetka] Failed to save the pickup point (HTTP ${response.status})`);
        }
    };

    // Reset a method row back to the "no point chosen" state.
    const resetPoint = (container, labelChoose) => {
        const selected = container.querySelector('.furgonetka-pickup-point__selected');
        const nameEl = container.querySelector('.furgonetka-pickup-point__name');
        const buttonLabel = container.querySelector('.furgonetka-pickup-point__button-label');

        if (selected) {
            selected.style.display = 'none';
        }
        if (nameEl) {
            nameEl.textContent = '';
        }
        if (buttonLabel && labelChoose) {
            buttonLabel.textContent = labelChoose;
        }
    };

    const SELECTED_METHOD_SELECTOR = 'input[type="radio"][name$="[method]"]:checked';

    // Show the pickup-point box only for the currently selected shipping method.
    const syncPickupPointVisibility = () => {
        const selected = document.querySelector(SELECTED_METHOD_SELECTOR);
        const selectedCode = selected ? selected.value : '';

        document.querySelectorAll('.furgonetka-pickup-point').forEach((container) => {
            container.style.display = container.dataset.methodCode === selectedCode ? '' : 'none';
        });
    };

    // Clear the selected point on every other method (one point per order).
    const resetOtherPoints = (current, labelChoose) => {
        document.querySelectorAll('.furgonetka-pickup-point').forEach((other) => {
            if (other !== current) {
                resetPoint(other, labelChoose);
            }
        });
    };

    // Remove the point saved for the cart (used when the shipping method changes).
    const clearSavedPoint = async (config) => {
        if (!config.clearUrl) {
            return;
        }

        await fetch(config.clearUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-Token': config.csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    };

    // Hide the "choose a pickup point" validation notice once a point is saved.
    const hidePickupPointNotice = (config) => {
        const own = document.getElementById('furgonetka-pickup-point-notice');
        if (own) {
            own.style.display = 'none';
        }

        const message = (config.labelNotice || '').trim();
        if (!message) {
            return;
        }

        document.querySelectorAll('.sylius-validation-error').forEach((error) => {
            if (error.textContent.trim() === message) {
                error.style.display = 'none';
            }
        });
    };

    // Display the chosen point on its method row.
    const showSelectedPoint = (container, point, config) => {
        hidePickupPointNotice(config);
        resetOtherPoints(container, config.labelChoose);

        const selected = container.querySelector('.furgonetka-pickup-point__selected');
        const nameEl = container.querySelector('.furgonetka-pickup-point__name');
        const buttonLabel = container.querySelector('.furgonetka-pickup-point__button-label');

        if (nameEl) {
            nameEl.textContent = point.name || point.code || '';
        }
        if (selected) {
            selected.style.display = '';
        }
        if (buttonLabel && config.labelChange) {
            buttonLabel.textContent = config.labelChange;
        }
    };

    // Open the Furgonetka map for a method and save the point picked in it.
    const openMap = async (container) => {
        const config = readConfig();
        const methodConfig = configFor(container);
        const address = readAddress(config);

        try {
            await loadSdk(config.sdkUrl);
        } catch {
            console.error('[furgonetka] Failed to load the Furgonetka map SDK');
            return;
        }

        if (!window.Furgonetka || typeof window.Furgonetka.Map !== 'function') {
            console.error('[furgonetka] Map SDK loaded but window.Furgonetka.Map is unavailable');
            return;
        }

        const params = {
            courierServices: methodConfig.courierService,
            availableServiceTypes: methodConfig.pointsTypes.length ? methodConfig.pointsTypes : undefined,
            city: address.city || undefined,
            street: address.street || undefined,
            countryCode: address.countryCode || undefined,
            env: config.env || undefined,
            apiKey: config.mapApiKey || undefined,
            callback: (response) => {
                if (!response || !response.point) {
                    return;
                }

                savePoint(container, response.point, config)
                    .then(() => showSelectedPoint(container, response.point, config))
                    .catch((error) => console.error(error));
            },
        };

        if (address.countryCode) {
            params.mapBounds = address.countryCode === 'PL' ? 'pl' : 'eu';
            params.countryCodesFilter = [address.countryCode];
        }

        new window.Furgonetka.Map(params).view();
    };

    // Open the map when a "choose pickup point" button is clicked.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('.furgonetka-pickup-point__button');
        if (!button) {
            return;
        }

        const container = button.closest('.furgonetka-pickup-point');
        if (!container) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openMap(container);
    });

    // Changing the shipping method clears the chosen point and refreshes visibility.
    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || target.type !== 'radio' || !target.name.endsWith('[method]')) {
            return;
        }

        const config = readConfig();
        hidePickupPointNotice(config);

        const containers = document.querySelectorAll('.furgonetka-pickup-point');
        if (!containers.length) {
            return;
        }

        containers.forEach((container) => resetPoint(container, config.labelChoose));
        clearSavedPoint(config).catch((error) => console.error(error));
        syncPickupPointVisibility();
    });

    // Sylius 1.14: move the saved point into the shipping-address column on the summary page.
    const placeCompletePoint = () => {
        const point = document.getElementById('furgonetka-complete-pickup-point');
        const shippingAddress = document.getElementById('sylius-shipping-address');
        if (!point || !shippingAddress) {
            return;
        }

        shippingAddress.appendChild(point);
        point.hidden = false;
    };

    const init = () => {
        placeCompletePoint();
        syncPickupPointVisibility();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
