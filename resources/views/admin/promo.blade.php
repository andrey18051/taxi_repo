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
            <p>Promo</p>
            <div class="card">
                <div class="card-body">
                <form action="{{ route('promoCreat') }}">
                @csrf
                   <div class="container">
                        <div class="row">
                            <div class="form-outline mb-3" >
                                <div class="row">
                                    <div class="col-2">
                                        <input type="text" id="promoCode" name="promoCode"
                                               placeholder="Промокод"
                                               autocomplete="off"
                                               class="form-control @error('promoCode') is-invalid @enderror"
                                               required/>

                                                @error('promoCode')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                    </div>
                                    <div class="col-2">
                                        <input type="number" id="promoSize" name="promoSize"
                                               min="1" max="100"
                                               value="1"
                                               title="Размер %"
                                               autocomplete="off" class="form-control"
                                               required/>
                                    </div>
                                    <div class="col-8">
                                        <input type="text" id="promoRemark" name="promoRemark"
                                               placeholder="Описание"
                                               autocomplete="off" class="form-control"
                                               required/>
                                    </div>
                                </div>
                            </div>

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
