<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">


        <div class="container" style="text-align: center">
            <div class="container" style="text-align: center">
                <div class="center gradient">
                    <span style="color:black">Сьогодні:</span>
                    <span style="color:black;; font-size:14px;">
                          <script>
                               document.write(date+" ");
                               document.write(thismonth+ " "+thisyear+" "+"року"+" — "+ DayofWeek);
                          </script>
                          (<span id="clockdat" style="color:blue;"></span>)
                        <span>
                        🌡️
                        {{\App\Http\Controllers\WeatherController::temp()}}
                        ℃
                    </span>
                    </span>
                </div>
            </div>
        </div>

    </div>
</nav>


