@extends('layouts.taxi')

@section('content')

    <!-- Section: Design Block -->
    <section class="">
        <!-- Jumbotron -->
        <div class="text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
            <div class="container">
                <div class="row align-items-center">
                    <h1  class="text-center">Таксі Київ (Київська область)</h1>
                    <div class="col-lg-6 ">
                        <p class="lead text-center">
                            Реєстрація
                        </p>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('sendConfirmCode') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="container">
                                    <div class="row">

                                        <!-- Phone input -->
                                        <div class="form-outline mb-2 col-12"  >
                                            <input type="text" class="form-control" name="username" placeholder="Телефон? Приклад: 0936665544">
                                        </div>

                                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                                    </div>

                                        <!-- Submit button -->
                                        <div class="row">
                                            <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                Отримати смс-код
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
        <!-- Jumbotron -->
    </section>
    <!-- Section: Design Block -->


@endsection
