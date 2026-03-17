<?php

namespace App\Console\Commands\Reports;

use App\Services\DeviceDailyReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

class ReportDeviceDailyCommand extends Command
{
    protected $signature = 'report:device-daily
        {startDate : Fecha inicio en formato dia-mes-año (d-m-Y)}
        {endDate? : Fecha fin en formato dia-mes-año (d-m-Y), por defecto hoy}
        {--output=table : table|csv|excel}
        {--disk=local : local|public}
        {--path= : Ruta relativa (ej: exports/device_report/reporte.xlsx)}';

    protected $description = 'Genera reporte diario de indicadores del dashboard entre fechas';

    public function handle(DeviceDailyReportService $service): int
    {
        $status = Command::SUCCESS;

        $output = $this->normalizeOutputType((string) $this->option('output'));
        $disk = $this->normalizeDiskType((string) $this->option('disk'));
        $path = $this->normalizeRelativePath($this->option('path'));
        $startDate = $this->parseDmYDate($this->argument('startDate'))?->startOfDay();

        $endDateInput = $this->argument('endDate');
        $endDate = $endDateInput
            ? $this->parseDmYDate($endDateInput)?->endOfDay()
            : now()->endOfDay();

        if (! $output) {
            $this->error('La opción --output no es válida. Use: table, csv o excel.');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS && ! $disk) {
            $this->error('La opción --disk no es válida. Use: local o public.');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS && $this->option('path') !== null && $path === null) {
            $this->error('La opción --path no es válida. Debe ser una ruta relativa sin "..".');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS && $disk === 'public' && $path !== null && ! str_starts_with($path, 'exports/')) {
            $this->error('La opción --path debe iniciar con "exports/" cuando --disk=public.');
            $status = Command::FAILURE;
        }

        if (! $startDate) {
            $this->error('La fecha de inicio no es válida. Use el formato d-m-Y (ej: 19-02-2026).');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS && $endDateInput && ! $endDate) {
            $this->error('La fecha de fin no es válida. Use el formato d-m-Y (ej: 19-02-2026).');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS && $startDate->greaterThan($endDate)) {
            $this->error('La fecha de inicio no puede ser mayor que la fecha de fin.');
            $status = Command::FAILURE;
        }

        if ($status === Command::SUCCESS) {
            $rows = $service->buildRows($startDate, $endDate);

            $this->info('Reporte diario de dispositivos');
            $this->line('Rango: '.$startDate->format('d-m-Y').' a '.$endDate->format('d-m-Y'));

            if ($output === 'table') {
                $this->table($this->deviceReportHeaders(), $rows);
            } else {
                $filePath = $this->exportDeviceReport($rows, $output, $startDate, $endDate, $disk ?? 'local', $path);
                $this->line('Archivo generado: '.$filePath);
            }
        }

        return $status;
    }

    private function parseDmYDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function deviceReportHeaders(): array
    {
        return [
            'Fecha',
            'Total Dispositivos',
            '% Reportados (Llegada)',
            '% Reportados (Salida)',
            'Total Reportados (Llegada)',
            'Total Reportados (Salida)',
        ];
    }

    private function normalizeOutputType(string $output): ?string
    {
        $normalized = strtolower(trim($output));

        if (in_array($normalized, ['table', 'csv', 'excel', 'xlsx'], true)) {
            return $normalized === 'xlsx' ? 'excel' : $normalized;
        }

        return null;
    }

    private function normalizeDiskType(string $disk): ?string
    {
        $normalized = strtolower(trim($disk));

        if ($normalized === '') {
            return 'local';
        }

        if (in_array($normalized, ['local', 'public'], true)) {
            return $normalized;
        }

        return null;
    }

    private function normalizeRelativePath(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $path = (string) $value;
        $path = str_replace('\\', '/', $path);
        $path = trim($path);

        if (
            $path === ''
            || str_contains($path, '..')
            || str_contains($path, ':')
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
        ) {
            return null;
        }

        return $path;
    }

    private function buildDeviceReportExportRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'Fecha' => $row[0],
                'Total Dispositivos' => $row[1],
                '% Reportados (Llegada)' => $row[2],
                '% Reportados (Salida)' => $row[3],
                'Total Reportados (Llegada)' => $row[4],
                'Total Reportados (Salida)' => $row[5],
            ];
        }, $rows);
    }

    private function exportDeviceReport(
        array $rows,
        string $output,
        Carbon $startDate,
        Carbon $endDate,
        string $disk,
        ?string $path
    ): string
    {
        $dateRange = $startDate->format('Ymd').'_'.$endDate->format('Ymd');
        $timestamp = now()->format('His');
        $extension = $output === 'csv' ? 'csv' : 'xlsx';

        $relativePath = $path;
        if (! $relativePath) {
            $relativePath = "exports/device_report/device_daily_report_{$dateRange}_{$timestamp}.{$extension}";
        }

        $filePath = Storage::disk($disk)->path($relativePath);

        $directory = dirname($filePath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $exportRows = $this->buildDeviceReportExportRows($rows);

        if ($output === 'csv') {
            $file = fopen($filePath, 'w');
            if ($file === false) {
                throw new RuntimeException('No fue posible crear el archivo CSV: '.$filePath);
            }

            fputcsv($file, $this->deviceReportHeaders());

            foreach ($exportRows as $row) {
                fputcsv($file, array_values($row));
            }

            fclose($file);
        } else {
            (new FastExcel($exportRows))->export($filePath);
        }

        return $filePath;
    }
}
