<?php

namespace App\Actions\Import;

use App\Models\User;
use App\Imports\UsersImport;
use App\Models\Configuration;
use App\Imports\DivipoleImport;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ImportFileAction
{
    use AsAction;
    public function handle(array $request)
    {
        ini_set('memory_limit', '-1');
        $modelImport = $this->getImport($request['route']);
        if($request['route']=="platform.settings" || $request['route']=="settings.import"){
            $configuration = Configuration::first();
            $attachment=$configuration->attachment()->get();
            if (!$attachment) {
                throw new FileNotFoundException('Attachment not found.');
            }
            $path="app\\public\\".str_replace('/','\\',$attachment[0]->path);
            $file=storage_path($path.$attachment[0]->name.'.'.$attachment[0]->extension);
            if(!file_exists($file)){
                throw new FileNotFoundException();
            }
        }
        
        $user = User::find($request['userId']);
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
    public function getImport($route)
    {
        switch ($route) {
            case "platform.settings":
            case "settings.import":
                return new DivipoleImport();
            case "platform.systems.users":
            case "users.import":
                return new UsersImport();
        }
    }
}
