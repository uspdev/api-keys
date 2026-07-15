<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-api-keys-modal="create" style="display: none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ $storeUrl }}">
        @csrf
        <div class="modal-header">
          <h3 class="modal-title h5">Nova API Key</h3>
          <button type="button" class="close" aria-label="Fechar" data-api-keys-close>
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="{{ $componentId }}-name">Nome</label>
            <input id="{{ $componentId }}-name" name="name" type="text"
              class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255"
              required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="{{ $componentId }}-purpose">Tipo</label>
            <select id="{{ $componentId }}-purpose" name="purpose"
              class="form-control @error('purpose') is-invalid @enderror" required>
              @foreach ($purposes as $value => $label)
                <option value="{{ $value }}" @selected(old('purpose', array_key_first($purposes)) === $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @error('purpose')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="{{ $componentId }}-role">Papel</label>
            <select id="{{ $componentId }}-role" name="role"
              class="form-control @error('role') is-invalid @enderror" required>
              @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', array_key_first($roles)) === $value)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            @error('role')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group mb-0">
            <label for="{{ $componentId }}-expires-at">Expiração</label>
            <input id="{{ $componentId }}-expires-at" name="expires_at" type="date"
              class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at') }}">
            <small class="form-text text-muted">Deixe em branco para não expirar.</small>
            @error('expires_at')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-api-keys-close>Cancelar</button>
          <button type="submit" class="btn btn-primary">Criar chave</button>
        </div>
      </form>
    </div>
  </div>
</div>
