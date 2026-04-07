<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plurales para badges de filtros (tablas Orchid)
    |--------------------------------------------------------------------------
    |
    | Se usa cuando el filtro es multi-select (array) y se muestra el resumen
    | tipo "N <plural>". La llave debe ser el nombre de la columna (TD::make()).
    |
    | Ejemplo:
    | 'agent_id' => 'Agentes',
    | 'department' => 'Departamentos',
    |
    */
    'plurals' => [
        'operative'     => 'Agentes',
        'department'    => 'Departamentos',
        'municipality'  => 'Municipios',
        'position_name' => 'Puestos',
        'report_time'   => 'Horas programadas',
        'report_time_arrival' => 'Horas reporte (llegada)',
        'report_time_departure' => 'Horas reporte (salida)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolvers (IDs -> nombres)
    |--------------------------------------------------------------------------
    |
    | Para filtros multi-select que envían IDs (por ejemplo Relation en Selection)
    | y no hay `options` disponibles en el header. Permite resolver los IDs al
    | texto a mostrar consultando el modelo.
    |
    | Formato:
    | 'operative' => ['model' => \App\Models\User::class, 'key' => 'id', 'label' => 'name']
    |
    */
    'resolvers' => [
        'operative' => [
            'model' => \App\Models\User::class,
            'key'   => 'id',
            'label' => 'name',
        ],
        'report_time' => [
            'model' => \App\Models\FilterHours::class,
            'key'   => 'id',
            'label' => 'hour',
        ],
    ],
];
