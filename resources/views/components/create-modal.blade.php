<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-api-keys-modal="form" style="display: none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ $apiKeyForm['action'] }}" data-api-keys-form-element>
        @csrf
        <input type="hidden" name="_api_keys_owner_alias" value="{{ $ownerAlias }}">
        <input type="hidden" name="_api_keys_owner_key" value="{{ $ownerRouteKey }}">
        <input type="hidden" name="_api_keys_operation" value="{{ $apiKeyForm['operation'] }}">
        <input type="hidden" name="_api_keys_id" value="{{ $apiKeyForm['api_key_id'] }}">

        <div class="modal-header">
          <h3 class="modal-title h5" data-api-keys-form-title>{{ $apiKeyForm['title'] }}</h3>
          <button type="button" class="close" aria-label="Fechar" data-api-keys-close>
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="{{ $componentId }}-name">Nome</label>
            <input id="{{ $componentId }}-name" name="name" type="text"
              class="form-control @isset($apiKeyForm['errors']['name']) is-invalid @endisset"
              value="{{ $apiKeyForm['values']['name'] }}" maxlength="255"
              required>
            @isset($apiKeyForm['errors']['name'])
              <div class="invalid-feedback">{{ $apiKeyForm['errors']['name'] }}</div>
            @endisset
          </div>

          <div class="form-group">
            <label for="{{ $componentId }}-purpose">Tipo</label>
            <select id="{{ $componentId }}-purpose" name="purpose"
              class="form-control @isset($apiKeyForm['errors']['purpose']) is-invalid @endisset" required>
              @foreach ($purposes as $value => $label)
                <option value="{{ $value }}" @selected($apiKeyForm['values']['purpose'] === $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @isset($apiKeyForm['errors']['purpose'])
              <div class="invalid-feedback">{{ $apiKeyForm['errors']['purpose'] }}</div>
            @endisset
          </div>

          <div class="form-group">
            <label for="{{ $componentId }}-role">Papel</label>
            <select id="{{ $componentId }}-role" name="role"
              class="form-control @isset($apiKeyForm['errors']['role']) is-invalid @endisset" required>
              @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected($apiKeyForm['values']['role'] === $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @isset($apiKeyForm['errors']['role'])
              <div class="invalid-feedback">{{ $apiKeyForm['errors']['role'] }}</div>
            @endisset
          </div>

          <div class="form-group mb-0">
            <label for="{{ $componentId }}-expires-at">Expiração</label>
            <input id="{{ $componentId }}-expires-at" name="expires_at" type="date"
              class="form-control @isset($apiKeyForm['errors']['expires_at']) is-invalid @endisset"
              value="{{ $apiKeyForm['values']['expires_at'] }}">
            <small class="form-text text-muted">Deixe em branco para não expirar.</small>
            @isset($apiKeyForm['errors']['expires_at'])
              <div class="invalid-feedback">{{ $apiKeyForm['errors']['expires_at'] }}</div>
            @endisset
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-api-keys-close>Cancelar</button>
          <button type="submit" class="btn btn-primary" data-api-keys-form-submit>
            {{ $apiKeyForm['submit_label'] }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
