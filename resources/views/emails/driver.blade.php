@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

Сообщение с сайта https://m.easy-order-taxi.site

@component('mail::button', ['url' => 'mailto:cartaxi4@gmail.com'])
 Ответить
@endcomponent

@endcomponent
