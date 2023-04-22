@component('mail::message')
# Тема: {{ $subject}}


{{$city}} <br>
{{$first_name}} <br>
{{$second_name}} <br>
{{$email}} <br>
{{$phone}} <br>
{{$brand}} <br>
{{$model}} <br>
{{$type}} <br>
{{$color}} <br>
{{$year}} <br>
{{$number}} <br>

@component('mail::button', ['url' => 'mailto:'. $email])
 Ответить
@endcomponent

@endcomponent
