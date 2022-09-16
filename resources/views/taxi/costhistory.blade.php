@extends('layouts.profile')

@section('content')
    {{-- print_r($response) --}}
    {{-- mb_substr($response['user_phone'], 3,12) --}}
    {{--print_r($authorization)--}}

    <order-component user_name = {{mb_substr($response['user_phone'], 3,12)}}  authorization = "{{$authorization}}" />

@endsection
