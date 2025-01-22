@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

<a href="{{ $url }}">Нажмите здесь для подтверждения данных</a>
@endcomponent
