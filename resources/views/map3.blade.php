<!DOCTYPE html>
<html>
<body>

<h1>My First Google Map</h1>

<div id="coordinates">
    Click somewhere on the map. Drag the marker to update the coordinates.
</div>
<div>
    <label>
        lat
        <input type="text" id="lat"/>
    </label>
    <label>
        lng
        <input type="text" id="lng"/>
    </label>
</div>
<div
    <label>
        lat
        <input type="text" id="lat2"/>
    </label>
    <label>
        lng
        <input type="text" id="lng2"/>
    </label>
</div>

<div id="googleMap" style="width:50%;height:400px;"></div>

<script>
    function myMap() {
        var marker;
        var myLatlng = {
            lat: 50.41897228100203,
            lng: 30.539817908280888
        };
        var marker2;
        var myLatlng2 = {
            lat: 50.47220906760347,
            lng: 30.730362036698857
        };

        var mapProp= {
            zoom: 10,
            center: myLatlng
        };
        var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);

        document.getElementById('lat').value = myLatlng.lat;
        document.getElementById('lng').value = myLatlng.lng;

        document.getElementById('lat2').value = myLatlng.lat;
        document.getElementById('lng2').value = myLatlng.lng;

        marker = new google.maps.Marker({
            position: myLatlng,
            map: map,
            draggable: true,
            label: 'Звідки'
        });

        marker.addListener('dragend', function(e) {
            var position = marker.getPosition();
            updateCoordinates(position.lat(), position.lng())
        });

        map.addListener('click', function(e) {
            marker.setPosition(e.latLng);
            updateCoordinates(e.latLng.lat(), e.latLng.lng())
        });

        marker2 = new google.maps.Marker({
            position: myLatlng2,
            map: map,
            draggable: true,
            label: 'Куди'
        });

        marker2.addListener('dragend', function(e) {
            var position2 = marker2.getPosition();
            updateCoordinates2(position2.lat(), position2.lng())
        });

        map.addListener('click', function(e) {
            marker2.setPosition(e.latLng);
            updateCoordinates2(e.latLng.lat(), e.latLng.lng())
        });

    }


    function updateCoordinates(lat, lng) {
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
    }
    function updateCoordinates2(lat, lng) {
        document.getElementById('lat2').value = lat;
        document.getElementById('lng2').value = lng;
    }

</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc&callback=myMap"></script>

</body>
</html>
