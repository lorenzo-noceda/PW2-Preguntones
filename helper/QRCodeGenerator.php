<?php

class QRCodeGenerator
{

    public static function getQrCodeParaImg($string)
    {
        $generator = new barcode_generator();
        $options = [
            'bc' => '#00FF0000',
            'cs' => '#FFFFFF', // espacios blancos
            'cm' => '#0d6efd', // módulos negros
        ];
        header('Content-type: svg+xml');
        return $generator->render_svg("qr", $string, $options);
    }


}


?>