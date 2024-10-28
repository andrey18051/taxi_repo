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
            <p>Cities</p>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('city-save') }}">
                                @csrf
                                <div class="container">
                                    <div class="row">

                                        <div class="form-outline mb-2 col-12" >
                                            <div class="row">
                                                <div class="col-3">
                                                    <input type="text" id="name" name="name" autocomplete="off" class="form-control" placeholder="name"
                                                    value="Kyiv City"
                                                    />
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" id="address" name="address" autocomplete="off" class="form-control" placeholder="188.190.245.102"/>
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" id="login" name="login" autocomplete="off" class="form-control" placeholder="login"
                                                    value="ONLINE56"
                                                    />
                                                </div>
                                                <div class="col-3">
                                                    <input type="text" id="password" name="password" autocomplete="off" class="form-control" placeholder="password"
                                                    value="gggdsh5+"
                                                    />
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
@endsection
