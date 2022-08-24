@extends('layouts.taxi')

@section('content')
    <section class="">
        <!-- Jumbotron -->
        <div class="px-4 py-5 px-md-5 text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
            <div class="container">
                <div class="row gx-lg-5 align-items-center">
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <h1 class="my-5 display-3 fw-bold ls-tight">
                            Найкраща пропозиція<br />
                            <span class="text-primary">для вашої поїздки</span>
                        </h1>
                        <p style="color: hsl(217, 10%, 50.8%)">
                            Максимум можливостей для пошуку найкращого варіанту
                        </p>
                    </div>

                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="card">
                            <div class="card-body py-5 px-md-5">
                                <form action="{{ route('registration') }}">
                                @csrf

                                <!-- 2 column grid layout with text inputs for the first and last names -->
                                    <div class="row">

                                        <!-- Phone input -->
                                        <div class="form-outline mb-4">
                                            <input type="text" class="form-control" name="phone" placeholder="0936665544">
                                            <label class="form-label" for="username">Телефон</label>
                                        </div>

                                        <!-- Password input -->
                                        <div class="form-outline mb-4">
                                            <input type="password" class="form-control" name="password" id="password">
                                            <label class="form-label" for="password">Пароль</label>
                                        </div>
                                        <!-- Password input confirm -->
                                        <div class="form-outline mb-4">
                                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="22223344">
                                            <label class="form-label" for="confirm_password">Підтвердити пароль</label>
                                        </div>
                                        <!-- Password input confirm -->
                                        <div class="form-outline mb-4">
                                            <input type="password" class="form-control" name="confirm_code" id="confirm_code" placeholder="6201">
                                            <label class="form-label" for="confirm_code">Код підтвердження з смс</label>
                                        </div>
                                        <!-- Submit button -->
                                        <button type="submit" class="btn btn-primary btn-block mb-4">
                                            Зареєструватись
                                        </button>

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
