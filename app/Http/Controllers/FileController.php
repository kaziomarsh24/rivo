<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KaziOmar\SmartUpload\Facades\Upload;

class FileController extends Controller
{
    public function handleRequest(Request $request)
    {
        $image = $request->file('image');
        if (!$image) {
            return response()->json(['error' => 'No image uploaded'], 400);
        }

        $imagePath = Upload::file($image)
            ->scaleDown(1920, 1080)
            ->toWebp(75)
            ->save('uploads')
            ->path;

        return response()->json([
            'message' => 'Request handled successfully',
            'path' => $imagePath,
            'url' => upload_url($imagePath),
        ]);
    }
}
