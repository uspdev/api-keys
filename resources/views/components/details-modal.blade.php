@php
  $detailsModalName = 'details-' . $apiKey->getKey();
  $detailsModalId = $componentId . '-' . $detailsModalName;
@endphp

<div id="{{ $detailsModalId }}" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"
  aria-labelledby="{{ $detailsModalId }}-title" data-api-keys-modal="{{ $detailsModalName }}" style="display: none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="{{ $detailsModalId }}-title" class="modal-title h5">Detalhes da API Key</h3>
        <button type="button" class="close" aria-label="Fechar" data-api-keys-close>
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <div class="font-weight-bold">{{ $apiKey->name }}</div>
          <code>{{ config('api-keys.credential_prefix', 'gpp') }}_{{ $apiKey->prefix }}</code>
        </div>

        <dl class="row mb-0">
          <dt class="col-sm-5">Status</dt>
          <dd class="col-sm-7">@include('api-keys::components.status-badge', ['apiKey' => $apiKey])</dd>

          <dt class="col-sm-5">Tipo</dt>
          <dd class="col-sm-7">{{ $purposes[$apiKey->purpose] ?? ucfirst($apiKey->purpose) }}</dd>

          <dt class="col-sm-5">Papel</dt>
          <dd class="col-sm-7">{{ $roles[$apiKey->role] ?? ucfirst($apiKey->role) }}</dd>

          <dt class="col-sm-5">Criada em</dt>
          <dd class="col-sm-7">{{ $apiKey->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>

          <dt class="col-sm-5">Expiração</dt>
          <dd class="col-sm-7">{{ $apiKey->expires_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>

          <dt class="col-sm-5">Último uso</dt>
          <dd class="col-sm-7">{{ $apiKey->last_used_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>

          <dt class="col-sm-5">Último IP</dt>
          <dd class="col-sm-7">{{ $apiKey->last_used_ip ?? '—' }}</dd>

          <dt class="col-sm-5">Acessos</dt>
          <dd class="col-sm-7">{{ number_format($apiKey->access_count) }}</dd>

          @if ($apiKey->created_by !== null)
            <dt class="col-sm-5">Criada pelo usuário ID</dt>
            <dd class="col-sm-7">{{ $apiKey->created_by }}</dd>
          @endif

          @if ($apiKey->revoked_at)
            <dt class="col-sm-5">Revogada em</dt>
            <dd class="col-sm-7">{{ $apiKey->revoked_at->format('d/m/Y H:i') }}</dd>
          @endif

          @if ($apiKey->revoked_by !== null)
            <dt class="col-sm-5">Revogada pelo usuário ID</dt>
            <dd class="col-sm-7">{{ $apiKey->revoked_by }}</dd>
          @endif
        </dl>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-api-keys-close>Fechar</button>
      </div>
    </div>
  </div>
</div>
