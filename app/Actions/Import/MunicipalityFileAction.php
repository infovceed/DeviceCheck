<?php

namespace App\Actions\Import;

use App\Imports\MunicipalityImport;
use App\Models\User;
use App\Models\Configuration;
use App\Jobs\NotifyUserOfCompletedImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Orchid\Attachment\Models\Attachment;

class MunicipalityFileAction
{
    use AsAction;

    private $model;
    public function __construct()
    {
        $this->model = new MunicipalityImport();
    }
    public function handle(array $arguments)
    {
        $configuration = Configuration::first();
        $attachmentID = $configuration->municipality_file;

        /** @var Attachment|null $attachment */
        $attachment = $configuration->attachment()->where('attachments.id', $attachmentID)->first();

        if ($attachment === null) {
            throw new FileNotFoundException('Attachment not found.');
        }
        $fragmentsPath=['app', 'public'];
        $attachmentPathFragments = explode('/', $attachment->path);
        $path = implode(DIRECTORY_SEPARATOR, [...$fragmentsPath, ...$attachmentPathFragments]);
        $file = storage_path("{$path}{$attachment->name}.{$attachment->extension}");
        if (!file_exists($file)) {
            throw new FileNotFoundException();
        }
        $user = User::find($arguments['userId']);
        $user->notify(new DashboardNotification(__('Municipalities import started'), __('The import has started. You will be notified when it is completed.')));
        $this->model->queue($file)->chain([
            new NotifyUserOfCompletedImport(
                $user,
                __('Municipalities import completed'),
                __('Municipalities import has been completed successfully.')
            )
        ]);
        return response()->json(['message' => __('file uploaded')], 200);
    }

    public function asJob($request)
    {
        return $this->handle($request);
    }
}
