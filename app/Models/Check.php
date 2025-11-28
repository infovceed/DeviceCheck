<?php

namespace App\Models;

use App\Models\Device;
use App\Models\Divipole;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereIn;
use App\Filters\Types\WhereDateIn;
use App\Filters\Types\WhereDistance500;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Check extends Model
{
    use HasFactory,Filterable,AsSource;

    

    /**
     * Virtual attributes appended to array / JSON.
     *
     * @var array
     */
    protected $appends = [
        'time_difference_minutes',
    ];

    protected $casts = [
        'time'        => 'datetime:H:i:s a',
        'report_time' => 'datetime:H:i:s a',
        'distance'    => 'float',
    ];

    protected $allowedFilters = [
        'department'    => WhereIn::class,
        'municipality'  => WhereIn::class,
        'position_name' => WhereIn::class,
        'tel'           => WhereIn::class,
        'device_key'    => WhereIn::class,
        'type'          => Like::class,
        'code'          => Where::class,
        'created_at'    => WhereDateIn::class,
        'distance'      => WhereDistance500::class,
        'report_time'   => Where::class,
    ];

    /**
     * Aplica filtros permitidos reutilizando la definición de $allowedFilters.
     * Pensado para exportaciones (queue) donde no existe Request.
     */
    public static function applyAllowedFilters($query, array $filters)
    {
        foreach ((new self)->allowedFilters as $column => $filterClass) {
            if (!array_key_exists($column, $filters) || $filters[$column] === null || $filters[$column] === '') {
                continue;
            }
            $value = $filters[$column];
            // Normalizar arrays provenientes del front
            if (is_string($value) && str_contains($value, ',')) {
                // Para filtros que aceptan múltiples valores separados por coma
                $valueParts = collect(preg_split('/\s*,\s*/', $value))->filter();
            } elseif (is_array($value)) {
                $valueParts = collect($value)->filter();
            } else {
                $valueParts = collect([$value])->filter();
            }

            if ($filterClass === WhereIn::class) {
                $query->whereIn($column, $valueParts->values()->all());
                continue;
            }
            if ($filterClass === Where::class) {
                // Usamos igualdad directa si sólo hay un valor
                $query->where($column, $valueParts->first());
                continue;
            }
            if ($filterClass === Like::class) {
                // Si vienen múltiples valores aplicamos OR LIKE
                $query->where(function($q) use ($column, $valueParts) {
                    foreach ($valueParts as $part) {
                        $q->orWhere($column, 'like', '%'.$part.'%');
                    }
                });
                continue;
            }
            if ($filterClass === WhereDateIn::class) {
                // Reutiliza lógica similar a la clase de filtro custom (OR por fecha)
                $dates = $valueParts->map(fn($d) => trim($d))
                    ->filter(fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d))
                    ->unique();
                if ($dates->isNotEmpty()) {
                    $query->where(function($q) use ($dates, $column) {
                        foreach ($dates as $d) {
                            $q->orWhereDate($column, $d);
                        }
                    });
                }
                continue;
            }
            if ($filterClass === WhereDistance500::class) {
                // Reutilizar lógica central: aceptar gt/le/num
                $raw = $valueParts->first();
                if ($raw !== null && $raw !== '') {
                    $lower = strtolower((string)$raw);
                    if (in_array($lower, ['gt','greater','1'], true)) {
                        $query->where('distance', '>', 0.5);
                    } elseif (in_array($lower, ['le','lte','less','0'], true)) {
                        $query->where('distance', '<=', 0.5);
                    } elseif (is_numeric($raw)) {
                        $km = floatval($raw) / 1000.0;
                        $query->where('distance', '>', $km);
                    }
                }
                continue;
            }
        }
        return $query;
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function divipole()
    {
        return $this->belongsTo(Divipole::class);
    }


    public function getTimeDifferenceMinutesAttribute(): ?string
    {
        if (empty($this->time) || empty($this->report_time)) {
            return null;
        }

        try {
            $report = Carbon::parse($this->report_time);
            $check  = Carbon::parse($this->time);
            $seconds = abs($report->diffInSeconds($check));
            $minutes = $seconds / 60;
            return number_format($minutes, 2);
        } catch (\Exception $e) {
            return null;
        }
    }
}
