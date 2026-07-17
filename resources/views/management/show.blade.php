@extends(config('api-keys.management.page.layout', 'layouts.app'))

@includeIf('laravel-usp-theme::blocos.datatable-simples')

@section('title', 'API Keys - ' . $ownerAlias . ' #' . $owner->getRouteKey())

@section('content')
  <div class="container-fluid py-4">
    <div class="mb-3">
      <a href="{{ route('api-keys.management.owners', ['ownerAlias' => $ownerAlias]) }}" class="small">&larr;
        Recursos de {{ ucfirst($ownerAlias) }}</a>
    </div>

    <x-api-keys::manager :owner="$owner" :owner-alias="$ownerAlias" />
  </div>
@endsection
