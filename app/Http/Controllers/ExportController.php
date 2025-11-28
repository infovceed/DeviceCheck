<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');
        // Restringir carpeta y evitar path traversal
        if ($path === '' || !str_starts_with($path, 'exports/') || str_contains($path, '..')) {
            abort(403);
        }
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }
        $fullPath = Storage::disk('public')->path($path);
        return response()->download($fullPath);
    }
}
