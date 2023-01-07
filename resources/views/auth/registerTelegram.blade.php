@extends('layouts.taxiNewCombo')

@section('content')
        <div class="container  wrapper">
            {{$info}}
        </div>

<!-- Section: Design Block -->
<section class="">
    <!-- Jumbotron -->

    <div class="row justify-content-center">
        <div class="col-md-8">

                    <div class="card">
                        <div class="card-header">{{ __('Email') }}</div>

                        <div class="card-body">
                            <form action="{{ route('registerTelegram') }}">
                                @csrf

                                <input type="hidden" id="name" name="name"  value="{{$params['name']}}">
                                <input type="hidden" id="telegram_id" name="telegram_id"  value="{{$params['telegram_id']}}">


                                <div class="row mb-3">
                                    <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Адреса електронної пошти') }}</label>

                                    <div class="col-md-6">
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                        @error('email')
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                </div>


                                <div class="row mb-0">
                                    <div class="col-md-8 offset-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            {{ __('Відправити') }}
                                        </button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>

        <br>

</section>

@endsection
