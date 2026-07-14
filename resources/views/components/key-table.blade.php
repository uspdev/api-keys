@if ($apiKeys->isEmpty())
  <div class="alert alert-light border" role="status">
    Nenhuma API Key cadastrada para este recurso.
  </div>
@else
  <div class="table-responsive">
    <table class="table table-hover align-middle">
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
          <tr>
            <td>
              <div class="font-weight-bold">{{ $apiKey->name }}</div>
              <small class="text-muted">
                {{ config('api-key.credential_prefix', 'gpp') }}_{{ config('api-key.credential_version', 'v1') }}_{{ $apiKey->prefix }}
              </small>
            </td>
            <td>{{ $purposes[$apiKey->purpose] ?? ucfirst($apiKey->purpose) }}</td>
            <td>{{ $roles[$apiKey->role] ?? ucfirst($apiKey->role) }}</td>
            <td>@include('api-keys::components.status-badge', ['apiKey' => $apiKey])</td>
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
              <details>
                <summary class="btn btn-sm btn-outline-secondary d-inline-block">Detalhes</summary>
                <div class="card card-body text-left mt-2" style="min-width: 260px;">
                  <dl class="mb-2 small">
                    <dt>Criada em</dt>
                    <dd>{{ $apiKey->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt>Expiração</dt>
                    <dd>{{ $apiKey->expires_at?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                    <dt>Último IP</dt>
                    <dd>{{ $apiKey->last_used_ip ?? '—' }}</dd>
                    @if ($apiKey->created_by !== null)
                      <dt>Criada pelo usuário ID</dt>
                      <dd>{{ $apiKey->created_by }}</dd>
                    @endif
                    @if ($apiKey->revoked_at)
                      <dt>Revogada em</dt>
                      <dd>{{ $apiKey->revoked_at->format('d/m/Y H:i') }}</dd>
                    @endif
                  </dl>
                </div>
              </details>

              @if ($apiKey->isActive())
                <form method="POST" action="{{ $apiKey->managerRevokeUrl($owner, $ownerAlias) }}" class="d-inline"
                  data-api-key-revoke-form>
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
