@extends('layouts.taxi')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>
                <h1>Account</h1>
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


<form action="{{ route('profile-edit') }}">
    @csrf
    <input type="hidden" class="form-control" name="authorization" value="{{$authorization}}"><br>

    <input type="text" class="form-control" name="user_first_name" id="user_first_name" value="{{$response["user_first_name"]}}"><br>
    <input type="text" class="form-control" name="user_middle_name" id="user_middle_name" value="{{$response["user_middle_name"]}}"><br>
    <input type="text" class="form-control" name="user_last_name" id="user_last_name" value="{{$response["user_last_name"]}}"><br>
    <input type="text" class="form-control" name="route_address_from" id="route_address_from" value="{{$response["route_address_from"]}}"><br>
    <input type="text" class="form-control" name="route_address_number_from" id="route_address_number_from" value="{{$response["route_address_number_from"]}}"d><br>
    <input type="text" class="form-control" name="route_address_entrance_from" id="route_address_entrance_from" value="{{$response["route_address_entrance_from"]}}"><br>
    <input type="text" class="form-control" name="route_address_apartment_from" id="route_address_apartment_from" value="{{$response["route_address_apartment_from"]}}"><br>

        <button type="submit" class="btn btn-primary">
            Update
        </button>

</form>



@endsection
