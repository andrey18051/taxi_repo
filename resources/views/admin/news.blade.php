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
            <div class="card">
                <div class="card-body">
                <form action="{{ route('news-save') }}">
                @csrf


                    <div class="container">
                        <div class="row">

                            <div class="form-outline mb-2 col-12" >
                                <div class="row">
                                    <div class="col-4">
                                        <input type="text" id="short" name="short" autocomplete="off" class="form-control"
                                               value="📢 " />
                                    </div>

                                    <div class="col-4">
                                        <textarea class="form-control" id="full" name="full" autocomplete="off" rows="10">
                                            🚧
                                        </textarea>
                                   </div>

                                    <div class="col-4">
                                        <input type="text" id="author" name="author" autocomplete="off" class="form-control"
                                               value="&#128662 Служба Таксі Лайт Юа" />
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
@endsection
