<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Notifications\DashboardNotification;
class DivipoleCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $csvPath;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($csvPath, $userId)
    {
        $this->csvPath = $csvPath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $localCsvPath = $this->csvPath;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $localCsvPath = str_replace('\\', '\\\\', $localCsvPath);
            }

            DB::statement("
                LOAD DATA LOCAL INFILE '{$localCsvPath}'
                INTO TABLE divipoles
                FIELDS TERMINATED BY ','
                ENCLOSED BY '\"'
                LINES TERMINATED BY '\\n'
                IGNORE 1 ROWS
                (
                    municipality_id,
                    department_id,
                    corporation_id,
                    code,
                    zone_code,
                    cad_pd_code,
                    position_code,
                    polling_station,
                    @kit_number_text
                )
                SET kit_number   = @kit_number_text, updated_at = NOW(), created_at = NOW()
            ");
            DB::statement("
                INSERT INTO Devices (divipole_id, page, type, status, updated_by, created_at, updated_at)
                SELECT id, NULL, 1, FALSE, NULL, NOW(), NOW()
                FROM divipoles
            ");
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new DashboardNotification(
                    __('Divipoles import completed'),
                    __('Divipoles import has been completed successfully.')
                ));
            }
        } catch (\Exception $e) {
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new DashboardNotification(
                    __('Divipoles import failed'),
                    __('There was an error importing the file: ') . $e->getMessage()
                ));
            }
            \Log::error('Error in DivipoleCsvImportJob: ' . $e->getMessage());
        }
    }
}
