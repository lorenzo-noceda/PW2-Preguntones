document.addEventListener("DOMContentLoaded", function () {
    // Obtener latitud y longitud de los inputs ocultos
    const latitud = parseFloat(document.getElementById("latitudUsuario").value);
    const longitud = parseFloat(document.getElementById("longitudUsuario").value);
    const userPosition = { lat: latitud, lng: longitud };

    function initMap() {
        const map = new google.maps.Map(document.getElementById("mapPerfil"), {
            center: userPosition,
            zoom: 10,
        });

        new google.maps.Marker({
            position: userPosition,
            map,
        });
    }

    // Cargar el script de Google Maps
    const script = document.createElement("script");
    script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDEM6PvxLZnVui_zkLYB9TqWDSzec3G2Uc";
    script.async = true;
    script.defer = true;

    // Inicializar el mapa cuando el script cargue
    script.onload = initMap;
    document.head.appendChild(script);
});
