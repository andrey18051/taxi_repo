@extends('layouts.admin')

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <p>News</p>
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Добавить новость
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card">
                                <div class="card-body">
                                    <form action="{{ route('news-save') }}">
                                        @csrf


                                        <div class="container">
                                            <div class="row">

                                                <div class="form-outline mb-2 col-12" >
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <input type="text" id="short" name="short" autocomplete="off" class="form-control"
                                                                   value="{{$news[0]}}" />
                                                        </div>
                                                    </div>
                                                    <div class="row" style="margin: 5px">
                                                        <div class="col-12">
                                        <textarea class="form-control" id="full" name="full" autocomplete="off" rows="10">
{{$news[1]}}
                                        </textarea>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <input type="text" id="author" name="author" autocomplete="off" class="form-control"
                                                                   value="{{$news[2]}}" />
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <!-- Submit button -->
                                            <div class="row">
                                                <a  class="btn btn-danger col-12" style="margin-top: 5px" href="/news">
                                                    Сгенерировать
                                                </a>
                                                <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                    Сохранить
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Добавить текст
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card-body">
                                <form action="{{ route('addTextForNews') }}">
                                    @csrf
                                    <div class="container">
                                        <div class="row">
                                            <div class="form-outline mb-2 col-12" >
                                                <div class="row">
                                                    <div class="col-12">
                                                        <textarea class="form-control" id="name" name="name" autocomplete="off" rows="10"  required>
                                                        </textarea>
                                                   </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Submit button -->
                                        <div class="row">
                                            <button type="reset" class="btn btn-danger col-12" style="margin-top: 5px">
                                                Очистить
                                            </button>
                                            <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                Сохранить
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Добавить цитату
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                        <div class="accordion-body">
                            <div class="card-body">
                                <form action="{{ route('quite-save') }}">
                                    @csrf
                                    <div class="container">
                                        <div class="row">


                                            <div class="form-outline mb-2 col-12" >
                                                <div class="row">
                                                    <div class="col-8">
                                    <textarea class="form-control" id="name" name="name" autocomplete="off"  required>

                                    </textarea>
                                                        <!--                                    <input type="text" class="form-control" id="name" name="name" autocomplete="off" placeholder="Цитата" required>-->
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="text" id="author" name="author" autocomplete="off" class="form-control" placeholder="Автор" />
                                                    </div>
                                                </div>
                                            </div>


                                        </div>

                                        <!-- Submit button -->
                                        <div class="row">
                                            <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                Сохранить
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    </div>
</div>
@endsection
