if (!window.FurgonetkaAdmin) {
  window.FurgonetkaAdmin = {};
}

/**
 * Furgonetka - integration management component (framework-agnostic)
 */
window.FurgonetkaAdmin.Integration = (function() {
  function registerPostMessagesHandler(baseShopUrl, connectUrl) {
    window.addEventListener('message', function (event) {
      if (event.origin !== baseShopUrl) {
        return;
      }

      if (event.data.action === 'connectAccount') {
        connectIntegration(connectUrl, event.data.testMode);
      }
    });
  }

  // Reveals the version-specific alert pre-rendered by _connection_alert.html.twig (Semantic UI
  // on 1.14, Bootstrap on 2.x). Falls back to window.alert if the template is absent.
  function showConnectionError(message) {
    const alertEl = document.getElementById('furgonetka-connection-alert');

    if (!alertEl) {
      window.alert(message || 'Furgonetka: connection error');
      return;
    }

    const textEl = document.getElementById('furgonetka-connection-alert-text');
    if (textEl) {
      textEl.textContent = message || alertEl.dataset.defaultMessage || '';
    }

    alertEl.style.display = '';
    alertEl.scrollIntoView({block: 'nearest'});
  }

  // Dynamically injected alerts miss Sylius' load-time close binding (1.14) and we keep the node
  // for re-use, so dismissal is wired here for both '.close' (Semantic) and '.btn-close' (Bootstrap).
  function wireDismiss() {
    const alertEl = document.getElementById('furgonetka-connection-alert');
    const closeEl = alertEl && alertEl.querySelector('.close, .btn-close');

    if (closeEl) {
      closeEl.addEventListener('click', function () {
        alertEl.style.display = 'none';
      });
    }
  }

  async function connectIntegration(connectUrl, testMode) {
    try {
      const rawResponse = await fetch(connectUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({testMode: testMode})
      });

      const response = await rawResponse.json().catch(() => ({}));

      if (rawResponse.ok && response.redirectUrl) {
        window.location = response.redirectUrl;
        return;
      }

      showConnectionError(response.error);
    } catch (error) {
      showConnectionError();
    }
  }

  function init(baseShopUrl, connectUrl) {
    registerPostMessagesHandler(baseShopUrl, connectUrl);
    wireDismiss();
  }

  return {
    init
  };
})();

/**
 * Furgonetka - error screen management component (framework-agnostic)
 */
window.FurgonetkaAdmin.ErrorScreen = (function() {
  function registerPostMessagesHandler(furgonetkaShopBaseUrl, redirectUrl) {
    window.addEventListener('message', function (event) {
      if (event.origin !== furgonetkaShopBaseUrl) {
        return;
      }

      if (event.data.action === 'furgonetkaRedirect') {
        window.location.href = redirectUrl;
      }
    });
  }

  function init(furgonetkaShopBaseUrl, redirectUrl) {
    registerPostMessagesHandler(furgonetkaShopBaseUrl, redirectUrl);
  }

  return {
    init
  };
})();
