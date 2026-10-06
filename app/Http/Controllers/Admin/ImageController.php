<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\WebpImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
{
    public function store(Request $request, WebpImages $images): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $path = $images->store($request->file('image'), 'site-media/content');
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['image' => $exception->getMessage()]);
        }

        return response()->json(['path' => $path, 'url' => url('/'.$path)], 201);
    }
}
