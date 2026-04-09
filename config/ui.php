<?php

$default = ['bg' => '#ffffff', 'text' => '#002060', 'hover' => '#eaf0ff'];

$greenSelva   = ['bg' => '#E8F5E9', 'text' => '#1B5E20', 'hover' => '#C8E6C9'];
$blueCielo    = ['bg' => '#E3F2FD', 'text' => '#1565C0', 'hover' => '#90CAF9'];
$blueClaro    = ['bg' => '#E3F2FD', 'text' => '#0D47A1', 'hover' => '#BBDEFB'];
$verdeLimon   = ['bg' => '#F1F8E9', 'text' => '#33691E', 'hover' => '#DCEDC8'];
$turquesa     = ['bg' => '#E0F7FA', 'text' => '#006064', 'hover' => '#B2EBF2'];
$lavanda      = ['bg' => '#EDE7F6', 'text' => '#311B92', 'hover' => '#D1C4E9'];
$naranjaClaro = ['bg' => '#FFF3E0', 'text' => '#E65100', 'hover' => '#FFE0B2'];
$verdeAgua    = ['bg' => '#E0F2F1', 'text' => '#004D40', 'hover' => '#B2DFDB'];
$azulOceano   = ['bg' => '#E1F5FE', 'text' => '#01579B', 'hover' => '#B3E5FC'];
$moradoClaro  = ['bg' => '#F3E5F5', 'text' => '#4A148C', 'hover' => '#E1BEE7'];

return [
    'department_button_colors' => [
        '_default'           => $default,
        'AMAZONAS'           => $greenSelva,
        'ANTIOQUIA'          => $blueClaro,
        'ARAUCA'             => $verdeLimon,
        'ATLANTICO'          => $turquesa,
        'BOGOTÁ D.C.'        => $moradoClaro,
        'BOLÍVAR'            => $naranjaClaro,
        'BOYACÁ'             => $greenSelva,
        'CALDAS'             => ['bg' => '#FCE4EC', 'text' => '#880E4F', 'hover' => '#F8BBD0'],
        'CAQUETÁ'            => $verdeAgua,
        'CASANARE'           => ['bg' => '#FFFDE7', 'text' => '#F57F17', 'hover' => '#FFF9C4'],
        'CAUCA'              => $lavanda,
        'CESAR'              => ['bg' => '#FFF8E1', 'text' => '#FF6F00', 'hover' => '#FFECB3'],
        'CHOCÓ'              => $azulOceano,
        'CÓRDOBA'            => $verdeAgua,
        'CUNDINAMARCA'       => $lavanda,
        'GUAINÍA'            => $blueCielo,
        'GUAVIARE'           => ['bg' => $greenSelva['bg'], 'text' => '#2E7D32', 'hover' => $greenSelva['hover']],
        'HUILA'              => $verdeLimon,
        'LA GUAJIRA'         => ['bg' => '#FFF8E1', 'text' => '#FF8F00', 'hover' => '#FFECB3'],
        'MAGDALENA'          => ['bg' => '#FFF3E0', 'text' => '#EF6C00', 'hover' => '#FFE0B2'],
        'META'               => $blueCielo,
        'NARIÑO'             => ['bg' => '#FBE9E7', 'text' => '#BF360C', 'hover' => '#FFCCBC'],
        'NORTE DE SANTANDER' => ['bg' => '#E8EAF6', 'text' => '#1A237E', 'hover' => '#C5CAE9'],
        'NORTE DE SAN'       => ['bg' => '#E8EAF6', 'text' => '#1A237E', 'hover' => '#C5CAE9'],
        'PUTUMAYO'           => ['bg' => '#E0F2F1', 'text' => '#00695C', 'hover' => '#B2DFDB'],
        'QUINDÍO'            => ['bg' => $greenSelva['bg'], 'text' => '#2E7D32', 'hover' => $greenSelva['hover']],
        'RISARALDA'          => $azulOceano,
        'SAN ANDRÉS'         => ['bg' => $turquesa['bg'], 'text' => '#00838F', 'hover' => $turquesa['hover']],
        'SANTANDER'          => $greenSelva,
        'SUCRE'              => $naranjaClaro,
        'TOLIMA'             => ['bg' => '#F3E5F5', 'text' => '#6A1B9A', 'hover' => '#E1BEE7'],
        'VALLE DEL CAUCA'    => $verdeAgua,
        'VALLE'              => $verdeAgua,
        'VAUPÉS'             => ['bg' => '#ECEFF1', 'text' => '#37474F', 'hover' => '#CFD8DC'],
        'VICHADA'            => ['bg' => '#F1F8E9', 'text' => '#827717', 'hover' => '#DCEDC8'],
    ],
];
