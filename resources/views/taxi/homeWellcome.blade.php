@extends('layouts.taxi')

@section('content')
    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="container" style="text-align: center; margin-top: 5px">
        <h1>Таксі Київ (Київська область)</h1>
        </div>
        <div class="container text-center">
            <h4>Ласкаво просимо до нашої служби таксі.</h4>
            <p>Ви можете легко  замовити поїздку по Київу та Київській області, скориставшись пошуком по вулицях, об'єктах та карті Google map.</p>
        </div>
        <div class="container text-center">
            <a  class="btn btn-outline-secondary  col-3" href="{{route('homeStreet')}}" target="_blank">Вулиці</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeObject')}}" target="_blank">Об'єкти</a>
            <a  class="btn btn-outline-secondary offset-1 col-3" href="{{route('homeMap')}}" target="_blank">Мапа</a>
        </div>

@endsection
