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


<br>
    <div class="row justify-content-center">
        <div class="col-md-4 col-md-offset-4 ">
            <p>Add bonus type</p>
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('bonus-store') }}">
                        @csrf
                        <div class="container">
                            <div class="row">

                                <div class="form-outline mb-2 col-12" >
                                    <div class="row">
                                        <div class="col-8">
                                            <input type="text" id="name" name="name" autocomplete="off" class="form-control" placeholder="название бонуса" />
                                        </div>
                                        <div class="col-4">
                                            <input type="number" id="size" name="size" autocomplete="off" class="form-control" placeholder="коэфициент"/>
                                        </div>

                                    </div>

                                </div>

                            </div>

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
