@extends('layouts.taxiNewCombo')

@section('content')
    @isset($info)
        <div class="container  wrapper">
            {{$info}}
        </div>
    @endisset

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Перш ніж продовжити введить код зі смс') }}</div>

                <div class="card-body">
                      <form class="d-inline"   action="{{route('verifySmsCode') }}">
                        @csrf
                          <input type="hidden" name="id"  value="{{$id}}"/>
                          <input type="hidden" name="user_phone"  value="{{$user_phone}}"/>


                        <input type="tel" class="form-control" id="confirmCode" name="confirm_code"/>
                          <div class="container text-center">
                              <div class="row">
                                  <a class="w-100 btn btn-danger" style="margin-top: 5px"
                                     href="{{route('homeCombo')}}" onclick="sessionStorage.clear();">
                                      Повернутися на головну
                                  </a>
                                  <button class="w-100 btn btn-primary" style="margin-top: 5px" type="submit">
                                      {{ __('Відправити') }}
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
