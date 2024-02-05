@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

@component('mail::button', ['url' => $url])
    {{$text_button}}
@endcomponent

@endcomponent
