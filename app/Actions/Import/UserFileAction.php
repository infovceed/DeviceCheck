<?php

namespace App\Actions\Import;

use App\Imports\UsersImport;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Http\Requests\User\ImportFileRequest;

class UserFileAction
{
    use AsAction;

    public function handle(ImportFileRequest $request)
    {
        $import = new UsersImport();

        $import->import($request->file('file'));

        return response()->json(['message' => __('file uploaded')], 200);
    }
}
