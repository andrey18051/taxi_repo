@extends('layouts.logout2')

@section('content')

    <!-- Section: Design Block -->
    <section class="">
        <!-- Jumbotron -->
        <div class="container text-center" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="container">
                <div class="row align-items-center">

                    <div class="col-lg-6 ">
                        <p class="lead text-center gradient">
                            Екстренна допомога
                        </p>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('callBack') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="container">
                                    <div class="row">

                                        <!-- Phone input -->
                                        <div class="form-outline mb-2 col-12"  >
                                            <input type="text" class="form-control" name="user_phone" value="{{$phone}}">
                                        </div>

                                        <script defer src="https://www.google.com/recaptcha/api.js"></script>
                                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                                    </div>

                                        <!-- Submit button -->
                                        <div class="row">
                                            <button type="submit" class="btn btn-primary col-12" style="margin-top: 5px">
                                                Надіслати
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
        <!-- Jumbotron -->
    </section>
    <!-- Section: Design Block -->


@endsection
