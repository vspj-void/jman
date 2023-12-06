<?php

require_once "config/config.php";

class Review
{
    public static function getReviewContents($cesta)
    {
        $cestaKomplet = UPLOAD_REVIEWS_DIRECTORY . DIRECTORY_SEPARATOR . $cesta;

        // Otevři soubor pro čtení
        $handle = fopen($cestaKomplet, 'r');

        if (!$handle) {
            return null;
        }

        // Získání obsahu souboru do proměnné
        $obsahSouboru = fread($handle, filesize($cestaKomplet));
        
        $obsahSouboru = htmlspecialchars($obsahSouboru);

        fclose($handle);

        return $obsahSouboru;
    }
}
