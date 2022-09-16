@extends('layouts.taxi')

@section('content')
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

/*        .full-height {
            height: 100vh;
        }*/

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 36px;
            padding: 20px;
        }
    </style>

    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title">
                Помилка 404 (Сторінку не знайдено)
            </div>
        </div>
    </div>
    <div class="container">
        <a  class="w-100 btn btn-outline-success btn-lg" href="{{route('home')}}">
            Замовити таксі
        </a>
    </div>
@endsection

</body>
</html>
