<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use App\Services\DeviceDailyReportService;
use Symfony\Component\Console\Command\Command;
use Rap2hpoutre\FastExcel\FastExcel;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

function parseDmYDate(?string $value): ?Carbon
{
    if (!$value) {
        return null;
    }

    try {
        return Carbon::createFromFormat('d-m-Y', $value);
    } catch (\Throwable $e) {
        return null;
    }
}

function deviceReportHeaders(): array
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

function normalizeOutputType(string $output): ?string
{
    $normalized = strtolower(trim($output));

    if (in_array($normalized, ['table', 'csv', 'excel', 'xlsx'], true)) {
        return $normalized === 'xlsx' ? 'excel' : $normalized;
    }

    return null;
}

function buildDeviceReportExportRows(array $rows): array
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

function exportDeviceReport(array $rows, string $output, Carbon $startDate, Carbon $endDate): string
{
    $directory = storage_path('app/exports/device_report');

    if (!File::isDirectory($directory)) {
        File::makeDirectory($directory, 0755, true);
    }

    $dateRange = $startDate->format('Ymd') . '_' . $endDate->format('Ymd');
    $timestamp = now()->format('His');
    $extension = $output === 'csv' ? 'csv' : 'xlsx';
    $filePath = $directory . DIRECTORY_SEPARATOR . "device_daily_report_{$dateRange}_{$timestamp}.{$extension}";

    $exportRows = buildDeviceReportExportRows($rows);

    if ($output === 'csv') {
        $file = fopen($filePath, 'w');
        fputcsv($file, deviceReportHeaders());

        foreach ($exportRows as $row) {
            fputcsv($file, array_values($row));
        }

        fclose($file);
    } else {
        (new FastExcel($exportRows))->export($filePath);
    }

    return $filePath;
}

Artisan::command('report:device-daily {startDate : Fecha inicio en formato dia-mes-año (d-m-Y)} {endDate? : Fecha fin en formato dia-mes-año (d-m-Y), por defecto hoy} {--output=table : table|csv|excel}', function () {
    $service = new DeviceDailyReportService();
    $status = Command::SUCCESS;
    $output = normalizeOutputType((string) $this->option('output'));
    $startDate = parseDmYDate($this->argument('startDate'))?->startOfDay();
    $endDateInput = $this->argument('endDate');
    $endDate = $endDateInput
        ? parseDmYDate($endDateInput)?->endOfDay()
        : now()->endOfDay();

    if (!$output) {
        $this->error('La opción --output no es válida. Use: table, csv o excel.');
        $status = Command::FAILURE;
    }

    if (!$startDate) {
        $this->error('La fecha de inicio no es válida. Use el formato d-m-Y (ej: 19-02-2026).');
        $status = Command::FAILURE;
    }

    if ($status === Command::SUCCESS && $endDateInput && !$endDate) {
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
        $this->line('Rango: ' . $startDate->format('d-m-Y') . ' a ' . $endDate->format('d-m-Y'));

        if ($output === 'table') {
            $this->table(deviceReportHeaders(), $rows);
        } else {
            $filePath = exportDeviceReport($rows, $output, $startDate, $endDate);
            $this->line('Archivo generado: ' . $filePath);
        }
    }

    return $status;
})->purpose('Genera reporte diario de indicadores del dashboard entre fechas');
