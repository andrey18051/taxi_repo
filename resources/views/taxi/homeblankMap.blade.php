@extends('layouts.taxiNewStreetEdit')

@section('content')

    {{--  dd($orderId[0])   --}}
    {{-- dd($routeArr) --}}

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div id="googleMap" style="width:100%;height:150px;"></div>
            </div>
        </div>
    </div>
    <br>
    <div class="container text-center">
        <a class="btn btn-outline-danger btn-circle"

           href="{{ route('callBackForm') }}"

           title="Екстренна допомога">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"  fill="currentColor" class="bi bi-telephone-inbound" viewBox="0 0 16 16">
                <path d="M15.854.146a.5.5 0 0 1 0 .708L11.707 5H14.5a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 1 0v2.793L15.146.146a.5.5 0 0 1 .708 0zm-12.2 1.182a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
            </svg>
        </a>
    </div>

                <div type="hidden" id="lat" value ={{$routeArr["from"]["lat"]}}/>
                <div type="hidden" id="lng" value = {{$routeArr["from"]["lng"]}} />
                <div type="hidden" id="lat2"  value ={{$routeArr["to"]["lat"]}} />
                <div type="hidden" id="lng2" value ={{$routeArr["to"]["lng"]}}/>

@if($routeArr["from"]["lat"] !== 0)
    <script defer type="text/javascript">

        /**
         * Карта Гугл
         */
        function myMap() {

            var myLatlng = {
                lat: {{$routeArr["from"]["lat"]}},
                lng: {{$routeArr["from"]["lng"]}},
            };

            var myLatlng2 = {
                lat: {{$routeArr["to"]["lat"]}},
                lng:  {{$routeArr["to"]["lng"]}}
            };

            var mapProp= {
                zoom: 15,
                center: myLatlng
            };
            var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);

            var directionsDisplay = new google.maps.DirectionsRenderer();
            var directionsService = new google.maps.DirectionsService();
            var request = {
                origin: myLatlng,
                destination: myLatlng2,
                travelMode: google.maps.TravelMode.DRIVING
            };

            directionsService.route(request, function (response, status) {
                directionsDisplay.setDirections (response);

            });
            directionsDisplay.setMap (map);
        }


    </script>
    <script defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc&callback=myMap"></script>
@endif
@endsection
