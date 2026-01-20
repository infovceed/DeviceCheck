<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviceBulkUpdateJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        private string $filePath,
        private User $user
    ) {}

    public function handle(): void
    {
        $updated = 0; $notFound = 0; $skipped = 0; $total = 0;
        try {
            $reader = IOFactory::createReaderForFile($this->filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                dispatch(new NotifyUserOfImportError(
                    $this->user,
                    'Excel bulk update error',
                    'The file is empty.'
                ));
                return;
            }

            $firstRowIndex = array_key_first($rows);
            $headerRow = $rows[$firstRowIndex] ?? [];
            $headerMap = [];
            foreach ($headerRow as $colLetter => $value) {
                $name = strtolower(trim((string)$value));
                if ($name !== '') {
                    $headerMap[$name] = $colLetter;
                }
            }

            foreach (['id_dispositivo_cambio','telefono','imei'] as $required) {
                if (!isset($headerMap[$required])) {
                    dispatch(new NotifyUserOfImportError(
                        $this->user,
                        'Excel bulk update error',
                        __('Missing required column: :col', ['col' => $required])
                    ));
                    return;
                }
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === $firstRowIndex) {
                    continue; // header
                }
                $total++;

                $idCell = $row[$headerMap['id_dispositivo_cambio']] ?? null;
                $telCell = $row[$headerMap['telefono']] ?? null;
                $imeiCell = $row[$headerMap['imei']] ?? null;

                $id = is_numeric($idCell) ? (int)$idCell : (int)trim((string)$idCell);
                $tel = $telCell !== null ? trim((string)$telCell) : null;
                $imei = $imeiCell !== null ? trim((string)$imeiCell) : null;

                if (!$id || ($tel === null && $imei === null)) {
                    $skipped++;
                    continue;
                }

                $device = Device::find($id);
                if (!$device) {
                    $notFound++;
                    continue;
                }

                if ($tel !== null && $tel !== '') {
                    $device->tel = $tel;
                }
                if ($imei !== null && $imei !== '') {
                    $device->imei = $imei;
                }
                $device->updated_by = $this->user->id;
                $device->save();
                $updated++;
            }

            dispatch(new NotifyUserOfCompletedImport(
                $this->user,
                'Devices bulk update completed',
                __('Updated: :u, Not found: :n, Skipped: :s', [
                    'u' => $updated,
                    'n' => $notFound,
                    's' => $skipped,
                ])
            ));
        } catch (\Throwable $e) {
            Log::error('DeviceBulkUpdateJob error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            dispatch(new NotifyUserOfImportError(
                $this->user,
                'Excel bulk update error',
                'An error occurred while processing the file.'
            ));
        }
    }
}
