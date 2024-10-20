document.getElementById('pais').addEventListener('change', function() {
    var paisId = this.value;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/PW2-Preguntones/registro/getCiudades?id=' + paisId, true);
    xhr.onload = function() {
        if (this.status === 200) {
            var ciudades = JSON.parse(this.responseText);
            var ciudadSelect = document.getElementById('ciudad');
            ciudadSelect.innerHTML = '<option value="">Selecciona una ciudad</option>';
            ciudades.forEach(function(ciudad) {
                var option = document.createElement('option');
                option.value = ciudad.id;
                option.textContent = ciudad.nombre;
                ciudadSelect.appendChild(option);
            });
        }
    };
    xhr.send();})