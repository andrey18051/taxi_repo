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
        <div class="alert alert-info text-center">
             {{session('cost')}}
        </div>
    </div>
@endif

@if(session('error'))
    <div class="container">
        <div class="alert alert-danger text-center">
            {{session('error')}}
        </div>
    </div>
@endif
