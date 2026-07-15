@props(['owner', 'ownerAlias' => null])

@php
  $managerData = \Uspdev\ApiKeys\Models\ApiKey::managerData($owner, $ownerAlias);
  $apiKeys = $managerData['apiKeys'];
  $ownerAlias = $managerData['ownerAlias'];
  $ownerRouteKey = $managerData['ownerRouteKey'];
  $componentId = $managerData['componentId'];
  $purposes = $managerData['purposes'];
  $roles = $managerData['roles'];
  $storeUrl = $managerData['storeUrl'];
  $createdApiKey = session('api-keys.created');
  $createdApiKeyToken = null;
  $createdApiKeyBelongsToManager =
      is_array($createdApiKey) &&
      ($createdApiKey['owner_alias'] ?? null) === $ownerAlias &&
      (string) ($createdApiKey['owner_key'] ?? '') === $ownerRouteKey;

  if ($createdApiKeyBelongsToManager && is_string($createdApiKey['encrypted_token'] ?? null)) {
      try {
          $createdApiKeyToken = app(\Illuminate\Contracts\Encryption\Encrypter::class)->decrypt(
              $createdApiKey['encrypted_token'],
              false,
          );
      } catch (\Throwable) {
          $createdApiKeyToken = null;
      } finally {
          session()->forget('api-keys.created');
      }
  }

  $createdApiKeyBelongsToManager = $createdApiKeyBelongsToManager && is_string($createdApiKeyToken);
  $hasCreationErrors =
      $errors->has('name') || $errors->has('purpose') || $errors->has('role') || $errors->has('expires_at');
@endphp

<section id="{{ $componentId }}" class="api-keys-manager" data-api-keys-manager>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h2 class="h5 mb-1">API Keys</h2>
      <p class="text-muted mb-0">Credenciais vinculadas a este recurso.</p>
    </div>

    <button type="button" class="btn btn-primary" data-api-keys-open="create">
      Nova API Key
    </button>
  </div>

  @if (session('api-keys.message'))
    <div class="alert alert-success" role="status">
      {{ session('api-keys.message') }}
    </div>
  @endif

  @include('api-keys::components.key-table', [
      'apiKeys' => $apiKeys,
      'owner' => $owner,
      'ownerAlias' => $ownerAlias,
  ])

  @include('api-keys::components.create-modal', ['storeUrl' => $storeUrl])

  @if ($createdApiKeyBelongsToManager)
    @include('api-keys::components.secret-modal', ['token' => $createdApiKeyToken])
  @endif
</section>

@once
  <style>
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

      if (modal.dataset.apiKeyModal === 'secret') {
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

    manager.querySelectorAll('[data-api-keys-open]').forEach((button) => {
      button.addEventListener('click', () => {
        const modal = manager.querySelector(`[data-api-keys-modal="${button.dataset.apiKeyOpen}"]`);
        openModal(modal);
      });
    });

    manager.querySelectorAll('[data-api-keys-close]').forEach((button) => {
      button.addEventListener('click', () => closeModal(button.closest('.modal')));
    });

    manager.querySelectorAll('[data-api-keys-copy]').forEach((button) => {
      button.addEventListener('click', async () => {
        const value = manager.querySelector(button.dataset.apiKeyCopy);

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

    @if ($hasCreationErrors)
      openModal(manager.querySelector('[data-api-keys-modal="create"]'));
    @elseif ($createdApiKeyBelongsToManager)
      openModal(manager.querySelector('[data-api-keys-modal="secret"]'));
    @endif
  })();
</script>
