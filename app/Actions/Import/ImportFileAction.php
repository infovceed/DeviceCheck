<?php

namespace App\Actions\Import;

use App\Models\User;
use App\Imports\UsersImport;
use App\Models\Configuration;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Orchid\Attachment\Models\Attachment;

class ImportFileAction
{
    use AsAction;

    public function handle(array $request)
    {
        ini_set('memory_limit', '-1');

        $user = User::find($request['userId']);
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User not found for import.');
        }

        $modelImport = $this->getImport($request['route'], $user);
        $file = null;

        if (in_array($request['route'], ["platform.settings", "settings.import", "platform.systems.users", "users.import"], true)) {
            $configuration = Configuration::first();

            /** @var Attachment|null $attachment */
            $attachment = $configuration->attachment()->first();

            if ($attachment === null) {
                throw new FileNotFoundException('Attachment not found.');
            }

            $path = 'app\\public\\' . str_replace('/', '\\', $attachment->path);
            $file = storage_path($path . $attachment->name . '.' . $attachment->extension);
            if (!file_exists($file)) {
                throw new FileNotFoundException();
            }
        }

        if ($file === null) {
            throw new FileNotFoundException('Import file not found for the selected route.');
        }

        $user->notify(new DashboardNotification(__('Import started'), __('The import has started. You will be notified when it is completed.')));
        $modelImport->queue($file)->chain([
            new NotifyUserOfCompletedImport($user)
        ]);
        return response()->json(['message' => __('file uploaded')], 200);
    }

    public function asJob($request)
    {
        return $this->handle($request);
    }
    public function getImport($route, User $user)
    {
        if (in_array($route, ["platform.systems.users", "users.import"], true)) {
            return new UsersImport($user);
        }

        throw new \InvalidArgumentException('Unsupported import route: ' . (string) $route);
    }
}
