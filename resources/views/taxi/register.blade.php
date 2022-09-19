@extends('layouts.taxi2')

@section('content')
    <section class="">
        <!-- Jumbotron -->
        <div class="text-center" style="background-color: hsl(0, 0%, 96%)">
            <div class="container">
                <div class="row align-items-center">

                    <div class="col-lg-6 ">
                        <p class="lead">
                            Реєстрація
                        </p>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('registration') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="container">
                                        <div class="row">
                                            <!-- Phone input -->
                                            <div class="form-outline mb-4">
                                                <input type="text" class="form-control" name="phone" placeholder="Телефон? Приклад: 0936665544">
                                            </div>

                                            <!-- Password input -->
                                            <div class="form-outline mb-4">
                                                <input type="password" class="form-control" name="password" placeholder="Пароль? Мінімально 7 букв або цифр" id="password">
                                            </div>
                                            <!-- Password input confirm -->
                                            <div class="form-outline mb-4">
                                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Повторіть введення пароля">
                                            </div>
                                            <!-- Password input confirm -->
                                            <div class="form-outline mb-4">
                                                <input type="text" class="form-control" name="confirm_code" id="confirm_code" placeholder="Код з смс? Приклад: 6201">
                                            </div>
                                            <!-- Submit button -->
                                            <button type="submit" class="btn btn-primary btn-block mb-4">
                                                Зареєструватись
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
