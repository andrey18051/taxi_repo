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
            <p>BlackList</p>
            <div class="row">
                <div class="card col-5">
                    <div class="card-body">
                        <form action="{{ route('addToBlacklist') }}">
                            @csrf
                            <div class="row">
                                <label for="$flexible_tariff_name" class="form-label">Add</label>
                                <select class="form-select" id="email" name="email" >

                                    @for ($i = 0; $i < count($emailArray); $i++)
                                        <option>{{$emailArray[$i]}}</option>

                                    @endfor

                                </select>
                            </div>
                                <!-- Submit button -->
                            <div class="row">
                                <button type="submit" class="btn btn-danger" style="margin-top: 5px">
                                    Сохранить
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
                <div class="card offset-2 col-5">
                    <div class="card-body">
                        <form action="{{ route('deleteFromBlacklist') }}">
                            @csrf
                            <div class="row">
                                <label for="$flexible_tariff_name" class="form-label">Delete</label>
                                <select class="form-select" id="email" name="email" >

                                    @for ($i = 0; $i < count($blackArray); $i++)
                                        <option>{{$blackArray[$i]}}</option>

                                    @endfor

                                </select>
                            </div>
                            <!-- Submit button -->
                            <div class="row">
                                <button type="submit" class="btn btn-primary" style="margin-top: 5px">
                                    Удалить
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection
