@props(['owner', 'ownerAlias' => null])

<section id="{{ $componentId }}" class="api-keys-manager" data-api-keys-manager>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h2 class="h5 mb-1">API Keys</h2>
      <p class="text-muted mb-0">Credenciais vinculadas a este recurso.</p>
    </div>

    <button type="button" class="btn btn-primary" data-api-keys-open="form"
      data-api-keys-form="{{ json_encode($newApiKeyForm) }}">
      Nova API Key
    </button>
  </div>

  @include('api-keys::components.key-table', [
      'apiKeys' => $apiKeys,
  ])

  @include('api-keys::components.create-modal', ['apiKeyForm' => $apiKeyForm])

  @if ($createdApiKeyBelongsToManager)
    @include('api-keys::components.secret-modal', [
        'token' => $createdApiKeyToken,
        'action' => $createdApiKeyAction,
    ])
  @endif
</section>

@once
  <style>
    [data-api-keys-manager] .api-keys-actions {
      display: inline-flex;
      flex-direction: column;
      gap: .25rem;
      min-width: 5.5rem;
      vertical-align: top;
    }

    [data-api-keys-manager] .api-keys-action,
    [data-api-keys-manager] .api-keys-action-button {
      margin: 0;
      width: 100%;
    }

    [data-api-keys-manager] .api-keys-secret-value {
      display: block;
      overflow-wrap: anywhere;
      white-space: pre-wrap;
    }

    [data-api-keys-manager] .api-keys-modal-backdrop {
      background: rgba(0, 0, 0, .5);
      bottom: 0;
      left: 0;
      position: fixed;
      right: 0;
      top: 0;
      z-index: 1040;
    }

    [data-api-keys-manager] .modal {
      z-index: 1050;
    }
  </style>
@endonce

<script>
  (() => {
    const manager = document.getElementById(@json($componentId));

    if (!manager || manager.dataset.apiKeyManagerReady === 'true') {
      return;
    }

    manager.dataset.apiKeyManagerReady = 'true';

    const closeModal = (modal) => {
      if (!modal) {
        return;
      }

      if (modal.dataset.apiKeysModal === 'secret') {
        const secret = modal.querySelector('.api-keys-secret-value');

        if (secret) {
          secret.textContent = 'O token não está mais disponível.';
        }

        modal.querySelectorAll('[data-api-keys-copy]').forEach((button) => button.disabled = true);
      }

      modal.style.display = 'none';
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      document.querySelectorAll('.api-keys-modal-backdrop').forEach((backdrop) => backdrop.remove());
      document.body.classList.remove('modal-open');
    };

    const openModal = (modal) => {
      if (!modal) {
        return;
      }

      modal.style.display = 'block';
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');

      const backdrop = document.createElement('div');
      backdrop.className = 'api-keys-modal-backdrop';
      backdrop.addEventListener('click', () => closeModal(modal));
      document.body.appendChild(backdrop);
      document.body.classList.add('modal-open');
    };

    // Atualiza o modal compartilhado antes de exibir a chave selecionada.
    const fillDetailsModal = (modal, details) => {
      modal.querySelectorAll('[data-api-keys-detail]').forEach((element) => {
        const field = element.dataset.apiKeysDetail;
        element.textContent = details[field] ?? '';
      });

      const status = modal.querySelector('[data-api-keys-detail="status"]');

      if (status) {
        status.className = `badge ${details.status_class ?? ''}`;
      }

      modal.querySelectorAll('[data-api-keys-detail-row]').forEach((element) => {
        const field = element.dataset.apiKeysDetailRow;
        element.hidden = details[field] === null || details[field] === undefined;
      });
    };

    // Alterna o mesmo formulário entre criação e renovação sem duplicar modais por linha.
    const fillApiKeyForm = (modal, formData) => {
      const form = modal.querySelector('[data-api-keys-form-element]');

      if (!form) {
        return;
      }

      form.action = formData.action;
      form.querySelector('[name="_api_keys_operation"]').value = formData.operation;
      form.querySelector('[name="_api_keys_id"]').value = formData.api_key_id;

      Object.entries(formData.values).forEach(([field, value]) => {
        const input = form.querySelector(`[name="${field}"]`);

        if (input) {
          input.value = value ?? '';
        }
      });

      modal.querySelector('[data-api-keys-form-title]').textContent = formData.title;
      modal.querySelector('[data-api-keys-form-submit]').textContent = formData.submit_label;
      form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback').forEach((feedback) => feedback.hidden = true);
    };

    manager.querySelectorAll('[data-api-keys-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const modal = manager.querySelector(`[data-api-keys-modal="${button.dataset.apiKeysOpen}"]`);

        if (modal && button.dataset.apiKeysDetails) {
          fillDetailsModal(modal, JSON.parse(button.dataset.apiKeysDetails));
        }

        if (modal && button.dataset.apiKeysForm) {
          fillApiKeyForm(modal, JSON.parse(button.dataset.apiKeysForm));
        }

        openModal(modal);
      });
    });

    manager.querySelectorAll('[data-api-keys-close]').forEach((button) => {
      button.addEventListener('click', () => closeModal(button.closest('.modal')));
    });

    manager.querySelectorAll('[data-api-keys-copy]').forEach((button) => {
      button.addEventListener('click', async () => {
        const value = manager.querySelector(button.dataset.apiKeysCopy);

        if (!value) {
          return;
        }

        if (!navigator.clipboard) {
          return;
        }

        await navigator.clipboard.writeText(value.textContent.trim());
        const originalText = button.textContent;
        button.textContent = 'Copiada';
        window.setTimeout(() => button.textContent = originalText, 1500);
      });
    });

    manager.querySelectorAll('[data-api-keys-revoke-form]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (!window.confirm('Revogar esta API Key? Ela deixará de autenticar imediatamente.')) {
          event.preventDefault();
        }
      });
    });

    manager.querySelectorAll('[data-api-keys-form-element]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        const operation = form.querySelector('[name="_api_keys_operation"]')?.value;

        if (
          operation === 'renew'
          && !window.confirm('Renovar esta API Key? Uma nova chave será criada e a anterior será revogada.')
        ) {
          event.preventDefault();
        }
      });
    });

    @if ($hasApiKeyFormErrors)
      openModal(manager.querySelector('[data-api-keys-modal="form"]'));
    @elseif ($createdApiKeyBelongsToManager)
      openModal(manager.querySelector('[data-api-keys-modal="secret"]'));
    @endif
  })();
</script>
