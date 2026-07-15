@extends(config('api-keys.management.page.layout', 'layouts.app'))

@section('title', 'API Keys - ' . $ownerAlias . ' #' . $owner->getRouteKey())

@section('content')
  <div class="container py-4">
    <div class="mb-4">
      <a href="{{ route('api-keys.admin.owners', ['ownerAlias' => $ownerAlias]) }}" class="small">&larr; Recursos de
        {{ ucfirst($ownerAlias) }}</a>

      <h1 class="h3 mb-1">API Keys</h1>
      <p class="text-muted mb-0">
        Gerencie as credenciais vinculadas a {{ $ownerAlias }} #{{ $owner->getRouteKey() }}.
      </p>
    </div>

    <x-api-keys::manager :owner="$owner" :owner-alias="$ownerAlias" />
  </div>
@endsection
