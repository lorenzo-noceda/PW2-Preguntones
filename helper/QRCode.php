<?php
class QRCode {

    public function __construct()
    {

    }

    public function getQrCodeImage ($string) {
        $contenidoQR = $string;

        // Captura el flujo de salida en lugar de enviar la imagen directamente
        ob_start();
        QRcode::png($contenidoQR, null, QR_ECLEVEL_L, 10, 2);
        $imageData = ob_get_contents();
        ob_end_clean();

        // Convierte el flujo de salida en una URL de datos
        $qrCodeDataUrl = 'data:image/png;base64,' . base64_encode($imageData);
    }

}


?>