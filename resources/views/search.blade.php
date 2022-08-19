@extends('layouts.search')

@section('content')
    <form action="{{route('search-cost')}}">
        @csrf
        <div class="container mt-5">
            <div classs="form-group">
                <input type="text" id="search" name="search" placeholder="Звідки (вулиця)" class="form-control" />
                <input type="text" id="from_number" name="from_number" placeholder="будинок" value="1" class="form-control" />
            </div>
        </div>

        <div class="container mt-5">
            <div classs="form-group">
                <input type="text" id="search1" name="search1" placeholder="Куди (вулиця)" class="form-control" />
                <input type="text" id="to_number" name="to_number" placeholder="будинок" value="1" class="form-control" />
            </div>
        </div>
        <div class="container mt-5">
            <button type="submit" class="btn btn-primary">
                Зберегти
            </button>
        </div>
    </form>



@endsection
