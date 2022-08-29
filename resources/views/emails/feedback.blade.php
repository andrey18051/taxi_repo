@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

@component('mail::button', ['url' => 'mailto:{{$email}}'])
 Ответить
@endcomponent

@endcomponent
