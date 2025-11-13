<?php

namespace App\Models;

use App\Models\Device;
use Orchid\Filters\Filterable;
use Orchid\Attachment\Attachable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Incident extends Model
{
    use HasFactory;
    use Attachable;
    use Filterable;
    /**
     * The attributes for which you can use sorting in url.
     * @var array
     */
    protected $fillable = [
        'subject',
        'message',
        'user_id',
        'device_id',
    ];
    protected $allowedSorts = [
        'id',
        'updated_at',
        'created_at',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function device()
    {
        return $this->belongsTo(Device::class);
    }
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function getContent()
    {
        return $this->message;
    }
}

