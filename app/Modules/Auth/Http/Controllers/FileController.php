<?php

namespace App\Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private files through temporary signed URLs (PRD 5.10). The
 * signed middleware guarantees expiry + integrity; the request can only
 * reach here with a valid signature.
 */
class FileController extends Controller
{
    public function show(Request $request, string $path): StreamedResponse
    {
        // Defense in depth: never allow traversal outside private root.
        if (str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('private');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
