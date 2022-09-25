@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

@component('mail::button', ['url' => 'mailto:cartaxi4@gmail.com'])
 Ответить
@endcomponent

@endcomponent
