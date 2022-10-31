@extends('layouts.newsList')

@section('content')
    <section class="">
        <!-- Jumbotron -->
        <div class="container text-center" style="background-color: hsl(0, 0%, 96%)">
            <br>
            <div class="container">
                <div class="row align-items-center">

                    <div class="col-lg-6 ">
                        <p class="lead gradient">
                            Відновлення доступу
                        </p>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body py-5 px-md-5">
                                <form action="{{ route('restore') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="container">
                                        <div class="row">

                                            <!-- Phone input -->
                                            <div class="form-outline mb-4">
                                                <input type="text" class="form-control" name="phone" placeholder="Телефон? Приклад: +380936665544">
                                            </div>

                                            <!-- Password input -->
                                            <div class="form-outline mb-4">
                                                <input type="password" class="form-control" name="password" placeholder="Пароль? Мінімально 6 букв або цифр" id="password">

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
                                                Змінити
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
