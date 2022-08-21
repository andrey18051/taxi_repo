@extends('layouts.cost')

@section('content')
    {{-- print_r($response)--}}

    <order-component user_name = {{mb_substr($response['user_phone'], 3,12)}}></order-component>
    <script src="/js/app.js"></script>



@endsection
