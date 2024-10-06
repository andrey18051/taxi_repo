@component('mail::message')
# Тема: {{ $subject}}

{{ $message}}

{{-- Если хотите добавить просто ссылку в тексте --}}
<a href="{{ $url }}">Нажмите здесь для перехода к заявке</a>

@component('mail::button', ['url' => 'mailto:' . $email])
 Написать письмо водителю
@endcomponent

@endcomponent
