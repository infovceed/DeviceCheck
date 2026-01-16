<?php

namespace App\Actions\Import;

use App\Imports\UsersImport;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use App\Http\Requests\User\ImportFileRequest;

class UserFileAction
{
    use AsAction;

    public function handle(ImportFileRequest $request)
    {
        $import = new UsersImport();

        $user = $request->user();
        if ($user) {
            $user->notify(new DashboardNotification(__('Users import started'), __('The import has started. You will be notified when it is completed.')));
        }

        // Procesar en cola y notificar al completar
        $import->queue($request->file('file'))
            ->chain([
                new NotifyUserOfCompletedImport(
                    $user,
                    __('Users import completed'),
                    __('Users import has been completed successfully.')
                )
            ]);

        return response()->json(['message' => __('file uploaded')], 200);
    }
}
