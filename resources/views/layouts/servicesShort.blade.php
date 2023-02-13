<div class="col-lg-3 col-sm-6 col-md-3">
    <a href="{{route('homeCombo')}}" target="_blank" style="text-decoration: none;">

        <p  class="gradient text-opacity-25" id="poslugy">
            <b>Послуги нашої служби.</b>
        </p>
    </a>
    <ul class="list-group mb-3">
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('orderReklama')}}" target="_blank"
               style="text-decoration: none;">Попереднє замовлення</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('driverReklama')}}" target="_blank"
               style="text-decoration: none;">Послуга "тверезий водій"</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('stationReklama')}}" target="_blank"
               style="text-decoration: none;">Таксі на вокзал</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('airportReklama')}}" target="_blank"
               style="text-decoration: none;">Таксі в аеропорт Бориспіль та Жуляни</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('regionReklama')}}" target="_blank"
               style="text-decoration: none;">Дешеве обласне міжміське таксі</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('tableReklama')}}" target="_blank"
               style="text-decoration: none;">Зустріч з табличкою</a>
        </li>
    </ul>
    <div class="text-center">
        <a href="{{route('homeCombo')}}" class="gradient-button animate-fading" target="_blank"
           onclick="sessionStorage.clear();">Замовити таксі</a>
    </div>

    <p  class="gradient text-opacity-25">
        <b>Замовити трансфер</b>
    </p>
    <div class="row">
        <div class="slideshow-container">

            <div class="mySlides fade">
                <a href="{{route('home')}}" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('img/kiyv2.jpg') }}" style="width:100%">

                </a>
            </div>

            <div class="mySlides fade">
                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('img/UZ.png') }}" style="width:100%">

                </a>
            </div>

            <div class="mySlides fade">
                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('img/borispol.png') }}" style="width:100%">

                </a>
            </div>

            <div class="mySlides fade">
                <a href="{{route('airportReklama')}}" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('img/sikorskogo.png') }}" style="width:100%">

                </a>
            </div>

            <div class="mySlides fade">
                <a href="{{route('stationReklama')}}" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('img/auto.jpeg') }}" style="width:100%">

                </a>
            </div>

        </div>
        <br>

        <div style="text-align:left">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
    </div>
    <ul class="list-group mb-3">
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('airportReklama')}}" target="_blank"
               style="text-decoration: none;">До аеропорту "Бориспіль"</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('airportReklama')}}" target="_blank"
               style="text-decoration: none;">До аеропорту "Киів" (Жуляни)</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('stationReklama')}}" target="_blank"
               style="text-decoration: none;">До залізничного вокзалу</a>
        </li>
        <li class="list-group-item d-flex justify-content-between lh-sm">
            <a href="{{route('stationReklama')}}" target="_blank"
               style="text-decoration: none;">До автовокзалу</a>
        </li>
    </ul>
 </div>

<script>
    let slideIndex = 0;
    showSlides();

    function showSlides() {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slideIndex++;
        if (slideIndex > slides.length) {slideIndex = 1}
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex-1].style.display = "block";
        dots[slideIndex-1].className += " active";
        setTimeout(showSlides, 2000); // Change image every 5 seconds
    }
</script>
