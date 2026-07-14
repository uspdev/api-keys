@extends(config('api-key.management.page.layout', 'layouts.app'))

@section('title', 'Gerenciamento de API Keys')

@section('content')
  <div class="container py-4">
    <div class="mb-4">
      @if ($ownerAlias !== null)
        <a href="{{ route('api-key.admin.index') }}" class="small">&larr; Todos os tipos</a>
      @endif

      <h1 class="h3 mb-1">Gerenciamento de API Keys</h1>
      <p class="text-muted mb-0">
        @if ($ownerAlias !== null)
          Selecione o recurso que deseja administrar em {{ ucfirst($ownerAlias) }}.
        @else
          Selecione o tipo de recurso que possui API Keys.
        @endif
      </p>
    </div>

    @if ($ownerAlias === null)
      @if (count($ownerTypes) === 0)
        <div class="alert alert-light border" role="status">
          Nenhum owner foi registrado em <code>api-key.owners</code>.
        </div>
      @else
        <div class="row">
          @foreach ($ownerTypes as $ownerType)
            <div class="col-md-6 col-lg-4 mb-3">
              <a href="{{ route('api-key.admin.owners', ['ownerAlias' => $ownerType['alias']]) }}"
                class="card h-100 text-decoration-none">
                <div class="card-body">
                  <h2 class="h5 text-dark">{{ $ownerType['label'] }}</h2>

                </div>
              </a>
            </div>
          @endforeach
        </div>
      @endif
    @elseif ($owners->isEmpty())
      <div class="alert alert-light border" role="status">
        Nenhum recurso disponível para administração.
      </div>
    @else
      <div class="list-group">
        @foreach ($owners as $entry)
          @php($owner = $entry['model'])
          <a href="{{ route('api-key.admin.show', ['ownerAlias' => $ownerAlias, 'owner' => $owner->getRouteKey()]) }}"
            class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
            <span>
              <strong>{{ $entry['label'] }}</strong>
              <small class="d-block text-muted">{{ $owner->getRouteKey() }}</small>
            </span>
            <span aria-hidden="true">&rarr;</span>
          </a>
        @endforeach
      </div>
    @endif
  </div>
@endsection
