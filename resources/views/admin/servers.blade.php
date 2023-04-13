@extends('layouts.server')

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <p>Cостояние серверов</p>
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Пинг
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-3">
                                            <p><b>31.43.107.151</b></p>
                                            {{$serversInfo[0]}}
                                        </div>

                                        <div class="col-md-3">
                                            <p><b>167.235.113.231</b></p>
                                            {{$serversInfo[1]}}
                                        </div>

                                        <div class="col-md-3">
                                            <p><b>134.249.181.173</b></p>
                                            {{$serversInfo[2]}}
                                        </div>

                                        <div class="col-md-3">
                                            <p><b>91.205.17.153</b></p>
                                            {{$serversInfo[3]}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Добавить
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card-body">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Добавить
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card-body">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

    </div>
</div>
@endsection
