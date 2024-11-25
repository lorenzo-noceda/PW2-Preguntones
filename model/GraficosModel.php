<?php

class GraficosModel
{

    private Graficador $graficador;

    public function __construct($graficador)
    {
        $this->graficador = $graficador;
    }

    public function reset () {
        $this->limpiarCarpetaDeAlmacen();
    }

    public function generarGraficoDeTorta(string $titulo, array $datosBD, array $colores, array $etiquetas, bool $porcentaje)
    {
        $this->graficador->crearGraficoDeTorta(350, 200, $titulo);
        $grafico = $this->graficador->asignarDatosGraficoDeTorta($datosBD);
        $this->graficador->asignarColoresGraficoDeTorta($colores, $grafico);
        $this->graficador->asignarTitulosPorDato($etiquetas, $grafico);
        $this->graficador->asignarFormatoDeNumeros($porcentaje ? "P" : "", $grafico);
        return $this->graficador->getGrafico();
    }

    public function generarGraficoDeBarras(
        string $titulo, string $nombreX, string $nombreY,
        array  $datosBD, array $categoriasX
    )
    {
        $this->limpiarCarpetaDeAlmacen();
        $this->graficador->crearGraficoDeBarras(
            350, 200,
            $datosBD, $categoriasX, $titulo, $nombreX, $nombreY
        );
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