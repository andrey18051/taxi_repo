@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

@component('mail::button', ['url' => 'mailto:' . $email])
 Написать письмо водителю
@endcomponent

@endcomponent
