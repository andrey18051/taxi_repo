@extends('layouts.taxi2')

@section('content')

    <div class="container" style="background-color: hsl(0, 0%, 96%)">
        <div class="container" style="text-align: center">
            <h1 class="gradient"><b>Політика конфіденційності</b></h1>
        </div>
        <div class="container">
            <div class="row gx-lg-5 align-items-center">
                <div class="text-center">
                    <p class="lead"> Застосовується Загальний регламент захисту персональних даних
                        <a href="https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0679">(GDPR)</a>
                        Європейського союзу
                    </p>
                    <p class="lead"> Будь ласка, ознайомтесь з Умовами користування сервісом за
                        <a href="{{ route('taxi-umovy') }}">посиланням</a>
                    </p>
                </div>
            </div>
        </div>
        <br>
    </div>


@endsection
