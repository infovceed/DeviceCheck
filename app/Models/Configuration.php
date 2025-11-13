<?php

namespace App\Models;

use Orchid\Attachment\Attachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Configuration extends Model
{
    use HasFactory;
    use Attachable;

    protected $fillable = [
        'divipole_file',
        'department_file',
        'municipality_file',
        'Devices_file',

    ];



}
