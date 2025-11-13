<?php

namespace App\Actions\Import;

use App\Models\User;
use App\Models\Configuration;
use App\Imports\DivipoleImport;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DivipoleFileAction
{
    use AsAction;
    private $model;
    public function __construct()
    {
        $this->model = new DivipoleImport();
    }
    public function handle(array $arguments)
    {
        $configuration = Configuration::first();
        $attachmentID=$configuration->divipole_file;
        $attachment = $configuration->attachment()->where('attachments.id',$attachmentID)->get();
        if (!$attachment) {
            throw new FileNotFoundException('Attachment not found.');
        }
        $path="app\\public\\".str_replace('/','\\',$attachment[0]->path);
        $file=storage_path($path.$attachment[0]->name.'.'.$attachment[0]->extension);
        if(!file_exists($file)){
            throw new FileNotFoundException();
        }
        $user = User::find($arguments['userId']);
        $user->notify(new DashboardNotification(__('Divipoles import started'), __('The import has started. You will be notified when it is completed.')));
        $this->model->queue($file)->chain([
            new NotifyUserOfCompletedImport(
                $user,
                __('Divipoles import completed'),
                __('Divipoles import has been completed successfully.')
            )
        ]);
        return response()->json(['message' => __('file uploaded')], 200);
    }

    public function asJob($request)
    {
        return $this->handle($request);
    }
}
