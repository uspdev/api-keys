@if ($apiKeys->isEmpty())
  <div class="alert alert-light border" role="status">
    Nenhuma API Key cadastrada para este recurso.
  </div>
@else
  <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover align-middle datatable-simples dt-paging-10 responsive">
      <caption class="sr-only">API Keys vinculadas ao recurso</caption>
      <thead>
        <tr>
          <th scope="col">Nome</th>
          <th scope="col">Tipo</th>
          <th scope="col">Papel</th>
          <th scope="col">Status</th>
          <th scope="col">Último uso</th>
          <th scope="col">Acessos</th>
          <th scope="col" class="text-right">Ações</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($apiKeys as $apiKey)
          {{-- Mantém no botão somente os dados necessários para preencher o modal único. --}}
          @php
            $apiKeyStatus = $apiKey->isRevoked()
                ? ['label' => 'Revogada', 'class' => 'badge-danger']
                : ($apiKey->isExpired()
                    ? ['label' => 'Expirada', 'class' => 'badge-warning']
                    : ['label' => 'Ativa', 'class' => 'badge-success']);
            $apiKeyDetails = [
                'name' => $apiKey->name,
                'prefix' => config('api-keys.credential_prefix', 'gpp') . '_' . $apiKey->prefix,
                'status' => $apiKeyStatus['label'],
                'status_class' => $apiKeyStatus['class'],
                'purpose' => $purposes[$apiKey->purpose] ?? ucfirst($apiKey->purpose),
                'role' => $roles[$apiKey->role] ?? ucfirst($apiKey->role),
                'created_at' => $apiKey->created_at?->format('d/m/Y H:i') ?? '—',
                'expires_at' => $apiKey->expires_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_at' => $apiKey->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
                'last_used_ip' => $apiKey->last_used_ip ?? '—',
                'access_count' => number_format($apiKey->access_count),
                'created_by' => $apiKey->created_by,
                'revoked_at' => $apiKey->revoked_at?->format('d/m/Y H:i'),
                'revoked_by' => $apiKey->revoked_by,
            ];
          @endphp
          <tr>
            <td>
              <div class="font-weight-bold">{{ $apiKey->name }}</div>
              <small class="text-muted">
                {{ config('api-keys.credential_prefix', 'gpp') }}_{{ $apiKey->prefix }}
              </small>
            </td>
            <td>{{ $purposes[$apiKey->purpose] ?? ucfirst($apiKey->purpose) }}</td>
            <td>{{ $roles[$apiKey->role] ?? ucfirst($apiKey->role) }}</td>
            <td>@include('api-keys::components.status-badge', ['apiKey' => $apiKey, 'status' => $apiKeyStatus])</td>
            <td>
              @if ($apiKey->last_used_at)
                <span title="{{ $apiKey->last_used_at->format('d/m/Y H:i:s') }}">
                  {{ $apiKey->last_used_at->diffForHumans() }}
                </span>
              @else
                <span class="text-muted">Nunca</span>
              @endif
            </td>
            <td>{{ number_format($apiKey->access_count) }}</td>
            <td class="text-right">
              <div class="api-keys-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary api-keys-action-button"
                  data-api-keys-open="details" data-api-keys-details='@json($apiKeyDetails)'>
                  Detalhes
                </button>

                @if ($apiKey->isActive())
                  <form method="POST" action="{{ $apiKey->managerRenewUrl($owner, $ownerAlias) }}"
                    class="api-keys-action" data-api-keys-renew-form>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary api-keys-action-button">Renovar</button>
                  </form>

                  <form method="POST" action="{{ $apiKey->managerRevokeUrl($owner, $ownerAlias) }}"
                    class="api-keys-action" data-api-keys-revoke-form>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger api-keys-action-button">Revogar</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @include('api-keys::components.details-modal')
@endif
