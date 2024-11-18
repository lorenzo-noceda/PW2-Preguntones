let map, marker;

function initMap() {
    // Coordenadas iniciales del mapa
    const initialPosition = { lat: -34.668294397380265, lng:-58.5666306124492 };
    map = new google.maps.Map(document.getElementById("map"), {
        center: initialPosition,
        zoom: 14,
    });

    map.addListener("click", (event) => {
        const clickedLocation = event.latLng;

        if (!marker) {
            marker = new google.maps.Marker({
                position: clickedLocation,
                map: map,
            });
        } else {
            marker.setPosition(clickedLocation);
        }

        // Almacenar las coordenadas en los campos ocultos
        document.getElementById("latitud").value = clickedLocation.lat();
        document.getElementById("longitud").value = clickedLocation.lng();
    });
}