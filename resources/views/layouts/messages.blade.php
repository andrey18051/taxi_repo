@if($errors->any())
    <div class="container">
        <div class="alert alert-danger text-center">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{$error}}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="container">
        <div class="alert alert-success text-center">
            {{session('success')}}
        </div>
    </div>
@endif

@if(session('cost'))
    <div class="container">
        <a  class="w-100 btn btn-primary btn-lg" href="{{route('costhistory-orders-neworder', $id)}}">
            {{session('cost')}}
        </a>
    </div>
    <div class="container" style="margin-top: 10px">
        <a  class="w-100 btn btn-danger btn-lg" href="{{route('home')}}">
            {{'Відмовитися'}}
        </a>
    </div>
@endif

@if(session('error'))
    <div class="container">
        <div class="alert alert-danger text-center">
            {{session('error')}}
        </div>
    </div>
@endif
