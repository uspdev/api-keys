@php
  $status = $status ?? ($apiKey->isRevoked()
      ? ['label' => 'Revogada', 'class' => 'badge-danger']
      : ($apiKey->isExpired()
          ? ['label' => 'Expirada', 'class' => 'badge-warning']
          : ['label' => 'Ativa', 'class' => 'badge-success']));
@endphp

<span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
