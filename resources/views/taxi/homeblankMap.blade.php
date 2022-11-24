@extends('layouts.taxiNewCombo')

@section('content')

    {{--  dd($orderId[0])   --}}
    {{-- dd($routeArr) --}}
    <br>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div id="googleMap" style="width:100%;height:150px;"></div>
            </div>
        </div>
    </div>

                <div type="hidden" id="lat" value ={{$routeArr["from"]["lat"]}}/>
                <div type="hidden" id="lng" value = {{$routeArr["from"]["lng"]}} />
                <div type="hidden" id="lat2"  value ={{$routeArr["to"]["lat"]}} />
                <div type="hidden" id="lng2" value ={{$routeArr["to"]["lng"]}}/>
    @if($routeArr["driver"]["lat"] !== null)
                <div type="hidden" id="lat3"  value ={{$routeArr["driver"]["lat"]}} />
                <div type="hidden" id="lng3" value ={{$routeArr["driver"]["lng"]}}/>
    @endif
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

    @if($routeArr["from"]["lat"] !== 0 && $routeArr["driver"]["lat"] !== null)
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

                var myLatlng3 = {
                    lat: {{$routeArr["driver"]["lat"]}},
                    lng:  {{$routeArr["driver"]["lng"]}}
                };

                var mapProp= {
                    zoom: 15,
                    center: myLatlng
                };
                var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);

                const image =
                    "{{ asset('img/taxiMarker.png') }}";
                var marker = new google.maps.Marker({
                    position: myLatlng3,
                    map: map,
                    icon: image,
                });

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
