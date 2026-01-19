<?php

namespace App\Actions\Import;

use App\Models\User;
use App\Models\Configuration;
use App\Imports\DevicesImport;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DevicesFileAction
{
    use AsAction;
    private $model;
    public function handle(array $arguments)
    {
        $configuration = Configuration::first();
        $attachmentID=$configuration->Devices_file;
        $attachment = $configuration->attachment()->where('attachments.id',$attachmentID)->get();
        if (!$attachment) {
            throw new FileNotFoundException('Attachment not found.');
        }
        $path="app/public/".str_replace('/','/',$attachment[0]->path);
        $file=storage_path($path.$attachment[0]->name.'.'.$attachment[0]->extension);
        if(!file_exists($file)){
            throw new FileNotFoundException();
        }
        $user = User::find($arguments['userId']);
        $user->notify(new DashboardNotification(__('Devices import started'), __('The import has started. You will be notified when it is completed.')));
        $this->model = new DevicesImport($user);
        $this->model->queue($file)->chain([
            new NotifyUserOfCompletedImport(
                $user,
                __('Devices import completed'),
                __('Devices import has been completed successfully.')
            )
        ]);
        return response()->json(['message' => __('file uploaded')], 200);
    }

    public function asJob($request)
    {
        return $this->handle($request);
    }
}
