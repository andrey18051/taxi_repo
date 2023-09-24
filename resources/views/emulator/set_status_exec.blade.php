@extends('layouts.admin')

@section('content')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                        Execution Status

                </div>
            </div>
        </div>
    </div>
</div>


<br>
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mx-auto">
                <div class="card">
                    <div class="card-body">


                        <div class="container">
                            <div class="row">

                                <div class="form-outline mb-2 col-12" >
                                    <div class="row">


                                        <label for="bonus_status" class="form-label"> BONUS</label>
                                        <select class="form-select" id="bonus_status" name="bonus_status" >

                                            @for ($i = 0; $i < count($statusArray); $i++)
                                                <option>{{$statusArray[$i]}}</option>

                                            @endfor

                                        </select>

                                    </div>

                                </div>

                            </div>

                            <div class="row">

                                <button type="submit" class="btn btn-primary " style="margin-top: 5px" id="bonus-exec-btn">
                                    Сохранить
                                </button>
                            </div>
                        </div>



                    </div>
                </div>
            </div>
            <div class="col-md-4  mx-auto ">
                <div class="card">
                    <div class="card-body">

                        <div class="container">
                            <div class="row">

                                <div class="form-outline mb-2 " >
                                    <div class="row">

                                        <label for="double_status" class="form-label"> DOUBLE</label>
                                        <select class="form-select" id="double_status" name="double_status" >

                                            @for ($i = 0; $i < count($statusArray); $i++)
                                                <option>{{$statusArray[$i]}}</option>

                                            @endfor

                                        </select>
                                    </div>

                                </div>

                            </div>

                            <div class="row">

                                <button type="submit" class="btn btn-primary " style="margin-top: 5px" id="double-exec-btn">
                                    Сохранить
                                </button>
                            </div>
                        </div>



                    </div>

                </div>

            </div>
        </div>
        <div class="container-fluid">
            <div class="row text-center">
                <div class="col-md-4 mx-auto">
                <a href="{{ route('update_status_exec') }}" class="btn btn-outline-success" target="_blank" style="margin-top: 5px">
                    Перейти к просмотру
                </a>
                </div>
            </div>
        </div>
   </div>



<script>
    $(document).ready(function() {
        $("#bonus-exec-btn").click(function() {
            var bonusStatus = $("#bonus_status").val(); // Получите значение из поля ввода или другого элемента
            $.ajax({
                url: '/emu/bonus-exec',
                type: 'POST',
                data: {
                    bonus_status: bonusStatus
                },
                success: function(response) {
                    // Обновите содержимое страницы или выполните другие необходимые действия
                    alert('Запрос выполнен успешно bonus_status');
                },
                error: function(error) {
                    alert('Произошла ошибка bonus_status' + bonusStatus );
                }
            });
        });

        $("#double-exec-btn").click(function() {
            var doubleStatus = $("#double_status").val(); // Получите значение из поля ввода или другого элемента
            $.ajax({
                url: '/emu/double-exec',
                type: 'POST',
                data: {
                    double_status: doubleStatus
                },
                success: function(response) {
                    // Обновите содержимое страницы или выполните другие необходимые действия
                    alert('Запрос выполнен успешно double_status');
                },
                error: function(error) {
                    alert('Произошла ошибка double_status');
                }
            });
        });

    });


</script>
@endsection
