@extends('layouts.taxiWelcome2')

@section('content')
    <div class="container" style="background-color: hsl(0, 0%, 96%)">

        <div class="container text-center">
            <p><b>Київ та область</b></p>
        </div>

            <div class="container text-center card">
                   <small>Оставаючись на сайті,  Ви погоджуєтесь
                       <br><a href="{{ route('taxi-gdbr') }}">на умови використання  особистой інформації.</a>
                   </small>
            </div>
        <br>

<!--            <p>Скористайтеся пошуком вулиць, об'єктів або картою Google map.</p>-->

        <div class="container text-center">
            <a  class="btn btn-outline-success btn-sm col-3" href="{{route('homeStreet', [$phone, $user_name])}}" target="_blank">Вулиці</a>
            <a  class="btn btn-outline-success btn-sm offset-1 col-3" href="{{route('homeObject', [$phone, $user_name])}}" target="_blank">Об'єкти</a>
            <a  class="btn btn-outline-success btn-sm offset-1 col-3" href="{{route('homeMap', [$phone, $user_name])}}" target="_blank">Мапа</a>
        </div>
        <br>
    </div>
@endsection
