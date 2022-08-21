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
        <a  class="w-100 btn btn-primary btn-lg" href="{{route('login-taxi')}}">
            {{session('cost')}}
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
