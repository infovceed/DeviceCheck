<?php

return [
    // Tiempo de vida de los enlaces firmados en minutos (por defecto 24h)
    'signed_url_ttl' => env('EXPORT_SIGNED_TTL_MINUTES', 60 * 24),
];

