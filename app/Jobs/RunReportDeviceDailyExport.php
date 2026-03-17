<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RunReportDeviceDailyExport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private string $startDateYmd,
        private string $endDateYmd,
        private string $disk,
        private string $path
    ) {
    }

    public function handle(): void
    {
        $start = Carbon::createFromFormat('Y-m-d', $this->startDateYmd)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $this->endDateYmd)->endOfDay();

        $exitCode = Artisan::call('report:device-daily', [
            'startDate' => $start->format('d-m-Y'),
            'endDate' => $end->format('d-m-Y'),
            '--output' => 'excel',
            '--disk' => $this->disk,
            '--path' => $this->path,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Falló la ejecución del comando report:device-daily.');
        }

        if (! Storage::disk($this->disk)->exists($this->path)) {
            throw new RuntimeException('El archivo de exportación no fue generado: ' . $this->path);
        }
    }
}
