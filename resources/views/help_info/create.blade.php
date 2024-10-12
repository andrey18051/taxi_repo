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

<div class="container mt-5">
    <h1 class="mb-4">Добавить информацию</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('help_info.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="page_number" class="form-label">Номер страницы:</label>
            <input type="text" name="page_number" id="page_number" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="info" class="form-label">Информация:</label>
            <textarea name="info" id="info" rows="5" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Загрузить изображение:</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>


@endsection
