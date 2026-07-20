if (!window.FurgonetkaAdmin) {
  window.FurgonetkaAdmin = {};
}

// Furgonetka admin modal (Sylius 1.x / Semantic UI).
window.FurgonetkaAdmin.Modal = (() => {
  // Set the iframe URL shown in the modal.
  function setUrl(url) {
    $('.furgonetka-modal .content iframe').attr('src', url);
  }

  // Set the modal title (header hidden when undefined).
  function setTitle(title) {
    if (title === undefined) {
      $('.furgonetka-modal .header').hide();
    } else {
      $('.furgonetka-modal .header').text(title).show();
    }
  }

  // Open the modal with the given URL and title.
  function openModal(url, title) {
    setUrl(url);
    setTitle(title);

    $('.furgonetka-modal').modal({
      onHidden: () => {
        setUrl('about:blank')
        setTitle();
      }
    }).modal('show');
  }

  // Close the currently open modal.
  function closeModal() {
    $('.furgonetka-modal').modal('hide');
  }

  return {
    setUrl,
    setTitle,
    openModal,
    closeModal,
  };
})();

// 1.14 has no event inside the order addresses column, so the chosen pickup point is rendered
// hidden in the sidebar and moved into the shipping-address segment (parity with 2.x).
(() => {
  const relocatePickupPoint = () => {
    const point = document.getElementById('furgonetka-pickup-point');
    const shippingAddress = document.getElementById('shipping-address');
    if (!point || !shippingAddress) {
      return;
    }

    shippingAddress.appendChild(point);
    point.hidden = false;
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', relocatePickupPoint);
  } else {
    relocatePickupPoint();
  }
})();
