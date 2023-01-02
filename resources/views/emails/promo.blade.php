@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

@component('mail::button', ['url' => 'https://m.easy-order-taxi.site/login-taxi'])
    Скористатися
@endcomponent

@endcomponent
