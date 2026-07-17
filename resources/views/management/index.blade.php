@extends(config('api-keys.management.page.layout', 'layouts.app'))

@includeIf('laravel-usp-theme::blocos.datatable-simples')

@section('title', 'Gerenciamento de API Keys')

@section('content')
  <div class="container-fluid py-4">
    <div class="mb-4">
      @if ($ownerAlias !== null)
        <a href="{{ route('api-keys.management.index') }}" class="small">&larr; Todos os tipos</a>
      @endif

      <h1 class="h3 mb-1">Gerenciamento de API Keys</h1>
      <p class="text-muted mb-0">
        @if ($ownerAlias !== null)
          Selecione o recurso que deseja gerenciar em {{ ucfirst($ownerAlias) }}.
        @else
          Selecione o tipo de recurso que possui API Keys.
        @endif
      </p>
    </div>

    @if ($ownerAlias === null)
      @if (count($ownerTypes) === 0)
        <div class="alert alert-light border" role="status">
          Nenhum owner foi registrado em <code>api-keys.owners</code>.
        </div>
      @else
        <div class="row">
          @foreach ($ownerTypes as $ownerType)
            <div class="col-md-6 col-lg-4 mb-3">
              <a href="{{ route('api-keys.management.owners', ['ownerAlias' => $ownerType['alias']]) }}"
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
        Nenhum recurso disponível para gerenciamento.
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover datatable-simples dt-paging-10 responsive">
          <caption class="sr-only">Recursos disponíveis para gerenciamento de API Keys</caption>
          <thead>
            <tr>
              <th scope="col">Recurso</th>
              <th scope="col">Identificador</th>
              <th scope="col" class="text-right">Ação</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($owners as $entry)
              @php($owner = $entry['model'])
              <tr>
                <td>{{ $entry['label'] }}</td>
                <td>{{ $owner->getRouteKey() }}</td>
                <td class="text-right">
                  <a href="{{ route('api-keys.management.show', ['ownerAlias' => $ownerAlias, 'owner' => $owner->getRouteKey()]) }}"
                    class="btn btn-sm btn-outline-primary">
                    Gerenciar
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
