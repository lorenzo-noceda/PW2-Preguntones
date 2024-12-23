<?php

class Graficador
{
    private Graph $grafico;

    public function __construct()
    {

    }

    public function crearGrafico($ancho, $alto)
    {
        $this->grafico = new Graph($ancho, $alto);
        $this->grafico->img->SetMargin(50, 100, 20, 50);
    }

    public function crearGraficoDeTorta($ancho, $alto, $titulo) {
        $this->grafico = new PieGraph($ancho, $alto);
        $this->grafico->title->Set($titulo);
        $this->grafico->SetBox();
    }

    public function crearGraficoDeBarras(
        $ancho = 350, $alto = 200,
        array $datosY, array $categoriasX, string $titulo, string $nombresX, string $nombresY
    ) {
        $this->grafico = new Graph($ancho, $alto); // Lienzo
        $this->grafico->SetScale("textlin"); // escala
        $this->grafico->xaxis->SetTickLabels($categoriasX); // nombres en X
        $this->grafico->xaxis->title->Set($nombresX);
        $this->grafico->yaxis->title->Set($nombresY);
        $barras = new BarPlot($datosY); // grafico;
        $barras->value->Show(); // Muestra los valores
        $barras->value->SetFormat('%d'); // Formato de número entero
        $barras->value->SetColor('black'); // Color del texto
        $this->grafico->Add($barras); // añadir grafico al lienzo
        $this->grafico->title->Set($titulo); // titulo del grafico
    }

    public function asignarDatosGraficoDeTorta($arrayDatos) {
        $torta = new PiePlot($arrayDatos);
        $this->grafico->Add($torta);
        return $torta;
    }

    public function asignarColoresGraficoDeTorta($arrayColores, PiePlot $torta) {
        $torta->SetSliceColors($arrayColores);
    }

    public function asignarTitulosPorDato($arrayTitulos, PiePlot $torta) {
        $torta->SetLegends($arrayTitulos);
        $torta->value->SetFormat('%0.0f');
    }

    public function asignarFormatoDeNumeros($formato, PiePlot $torta) {
        if ($formato == "P") {
            $torta->value->SetFormat('%0.1f%%');
        } else {
            $torta->value->SetFormat('%0.0f');
        }
    }

    public function setearEscala($string)
    {
        $this->grafico->setScale("textlin");
    }

    public function agregarDatosX($array, $tituloDatos, $tituloGrafico)
    {
        $this->grafico->title->Set($tituloGrafico);
        $this->grafico->xaxis->title->Set($tituloDatos);
        $this->grafico->xaxis->SetTickLabels($array);
    }

    public function agregarSerieY($array, $colorLinea, $grosor, $tituloY)
    {
        $this->grafico->yaxis->title->Set($tituloY); // Nombre eje Y
        $linePlot = new BarPlot($array);
        $linePlot->SetColor($colorLinea); // Color de la línea
        $linePlot->SetWeight($grosor); // Grosos de linea
        $linePlot->SetLegend("LOL");// Titulo de barra
        $this->grafico->Add($linePlot);  // Agregar la serie al gráfico
    }

    public function getGrafico()
    {
        $rutaGrafico = 'public/img/' . uniqid('grafico_', true) . '.png';
        $this->grafico->Stroke($rutaGrafico);
        return $rutaGrafico;
    }

    private function verVariable($data): void
    {
        echo '<pre class="text-white">' . print_r($data, true) . '</pre>';
    }


}

