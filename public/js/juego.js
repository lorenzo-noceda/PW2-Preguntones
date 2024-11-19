document.addEventListener("DOMContentLoaded", () => {
    const reloj = document.getElementById("reloj");
    const formPregunta = document.getElementById("form-pregunta");
    const tiempoInicio = parseInt(document.getElementById("tiempo_inicio").value, 10);
    const tiempoLimite = parseInt(document.getElementById("tiempo_limite").value, 10);
    const tiempoActual = Math.floor(Date.now() / 1000);

    let tiempoRestante = tiempoLimite - (tiempoActual - tiempoInicio);

    const manejarTiempoAgotado = () => {
        // Deshabilitar botones
        const botonesRespuestas = formPregunta.querySelectorAll("button[name='respuesta_id']");
        botonesRespuestas.forEach(boton => boton.disabled = true);

        // Mostrar mensaje de tiempo agotado
        reloj.textContent = "Tiempo agotado";

        // Enviar respuesta predeterminada
        const inputSinRespuesta = document.createElement("input");
        inputSinRespuesta.type = "hidden";
        inputSinRespuesta.name = "respuesta_id";
        inputSinRespuesta.value = "sin_respuesta"; // Cambiar según el backend
        formPregunta.appendChild(inputSinRespuesta);

        // Enviar formulario después de 1 segundo
        setTimeout(() => {
            formPregunta.submit();
        }, 1000);
    };

    if (tiempoRestante <= 0) {
        manejarTiempoAgotado();
    } else {
        // Configurar temporizador
        const temporizador = setInterval(() => {
            tiempoRestante--;

            if (tiempoRestante > 0) {
                reloj.textContent = tiempoRestante;
            } else {
                clearInterval(temporizador);
                manejarTiempoAgotado();
            }
        }, 1000); // Actualizar cada segundo
    }
});
