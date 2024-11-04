<?php

class QRCodeGenerator
{

    public static function getQrCodeParaImg($string)
    {
        $generator = new barcode_generator();
        header('Content-type: svg+xml');
        $svg = $generator->render_svg("qr", $string, "");
        return $svg;
    }


}


?>