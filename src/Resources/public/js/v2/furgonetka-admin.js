if (!window.FurgonetkaAdmin) {
  window.FurgonetkaAdmin = {};
}

// Furgonetka admin modal (Sylius 2.x / Bootstrap 5).
window.FurgonetkaAdmin.Modal = (() => {
  function getModalElement() {
    return document.querySelector('.furgonetka-modal');
  }

  // Set the iframe URL shown in the modal.
  function setUrl(url) {
    const iframe = document.querySelector('.furgonetka-modal .modal-body iframe');
    if (iframe) {
      iframe.setAttribute('src', url);
    }
  }

  // Set the modal title (empty when undefined).
  function setTitle(title) {
    const titleElement = document.querySelector('.furgonetka-modal .modal-title');
    if (!titleElement) {
      return;
    }
    titleElement.textContent = title === undefined ? '' : title;
  }

  // Open the modal with the given URL and title.
  function openModal(url, title) {
    const modalElement = getModalElement();
    if (!modalElement) {
      return;
    }

    setUrl(url);
    setTitle(title);

    modalElement.addEventListener('hidden.bs.modal', () => {
      setUrl('about:blank');
      setTitle();
    }, { once: true });

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  }

  // Close the currently open modal.
  function closeModal() {
    const modalElement = getModalElement();
    if (!modalElement) {
      return;
    }
    const instance = bootstrap.Modal.getInstance(modalElement);
    if (instance) {
      instance.hide();
    }
  }

  return {
    setUrl,
    setTitle,
    openModal,
    closeModal,
  };
})();
