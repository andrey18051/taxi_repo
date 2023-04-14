@extends('layouts.server')

@section('content')

<div class="container">
    <div class="row justify-content-center">

        <div class="col-md-12">

            <button class="btn btn-outline-primary" onclick="location.reload()">
                Cостояние серверов
            </button>
            <div class='progress-bar'>
                <div class='progress' style='max-width: 100%; --time: 900s'></div>
            </div>

            <div class="accordion" id="accordionExample" style="margin-top: 5px">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Статус
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        @for($i = 0; $i<=6; $i+=2)
                                        <div class="col-md-3">
                                            <p><b>{{$serversInfo[$i]}}</b></p>
                                            @if ($serversInfo[$i+1] == "Подключен")
                                                <p><b><span style="color: green">{{$serversInfo[$i+1]}}</span></b></p>
                                            @else
                                                <p><b><span style="color: red">{{$serversInfo[$i+1]}}</span></b></p>
                                            @endif
                                        </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Пинг
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        @for($i = 8; $i<=14; $i+=2)
                                        <div class="col-md-3">
                                            <p><b>{{$serversInfo[$i]}}</b></p>
                                            {{$serversInfo[$i+1]}}
                                        </div>
                                        @endfor
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
{{--                <div class="accordion-item">--}}
{{--                    <h2 class="accordion-header" id="headingThree">--}}
{{--                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">--}}
{{--                            Добавить--}}
{{--                        </button>--}}
{{--                    </h2>--}}
{{--                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">--}}
{{--                        <div class="accordion-body">--}}
{{--                            <div class="card-body">--}}

{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>

    </div>
</div>
@endsection
