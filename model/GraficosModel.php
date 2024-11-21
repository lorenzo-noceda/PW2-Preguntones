<?php

class GraficosModel {

    private Graficador $graficador;
    public function __construct($graficador)
    {
        $this->graficador = $graficador;
    }

    public function generarGraficoDeTortaPorSexos($datosBD)
    {
        $this->limpiarCarpetaDeAlmacen();
        $this->graficador->crearGraficoDeTorta(350, 200, "Usuarios por Sexo");
        $grafico = $this->graficador->asignarDatosGraficoDeTorta(
            [$datosBD[0]["cantidad"],
                $datosBD[1]["cantidad"],
                $datosBD[2]["cantidad"]
            ]);

        $this->graficador->asignarColoresGraficoDeTorta(["blue", "red", "green"], $grafico);
        $this->graficador->asignarTitulosPorDato(["Femenino", "Masculino", "Prefiere no decir"], $grafico);
        $this->graficador->asignarFormatoDeNumeros("P", $grafico);
        return $this->graficador->getGrafico();
    }

    private function limpiarCarpetaDeAlmacen()
    {
        $carpeta = 'public/img/';

        $carpeta = rtrim($carpeta, '/') . '/';

        if (is_dir($carpeta) && strpos(realpath($carpeta), realpath('public/img/')) === 0) {
            $archivos = glob($carpeta . '*');

            foreach ($archivos as $archivo) {
                if (is_dir($archivo)) {
                    rmdir($archivo);
                } else {
                    unlink($archivo);
                }
            }

        }
    }


}