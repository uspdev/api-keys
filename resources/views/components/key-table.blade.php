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
          <tr>
            <td>
              <div class="font-weight-bold">{{ $apiKey['name'] }}</div>
              <small class="text-muted">{{ $apiKey['credential'] }}</small>
            </td>
            <td>{{ $apiKey['purpose'] }}</td>
            <td>{{ $apiKey['role'] }}</td>
            <td>@include('api-keys::components.status-badge', ['status' => $apiKey['status']])</td>
            <td>
              @if ($apiKey['last_used_human'] !== null)
                <span title="{{ $apiKey['last_used_title'] }}">
                  {{ $apiKey['last_used_human'] }}
                </span>
              @else
                <span class="text-muted">Nunca</span>
              @endif
            </td>
            <td>{{ $apiKey['access_count'] }}</td>
            <td class="text-right">
              <div class="api-keys-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary api-keys-action-button"
                  data-api-keys-open="details" data-api-keys-details='@json($apiKey['details'])'>
                  Detalhes
                </button>

                @if ($apiKey['is_active'])
                  <button type="button" class="btn btn-sm btn-outline-primary api-keys-action-button"
                    data-api-keys-open="form" data-api-keys-form="{{ json_encode($apiKey['renewal_form']) }}">
                    Renovar
                  </button>

                  <form method="POST" action="{{ $apiKey['revoke_url'] }}"
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
