<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ChunkUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'file_name' => 'required|string',
            'file_chunk' => 'required|file',
        ]);

        $fileId = $request->input('file_id');
        $chunkIndex = (int)$request->input('chunk_index');
        $totalChunks = (int)$request->input('total_chunks');
        $originalName = $request->input('file_name');
        
        $tempDir = storage_path('app/temp_uploads/' . $fileId);
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true, true);
        }

        $chunkFile = $request->file('file_chunk');
        $chunkName = 'chunk_' . $chunkIndex;
        $chunkFile->move($tempDir, $chunkName);

        // Count how many chunks have actually been uploaded
        $files = File::files($tempDir);
        $uploadedChunks = 0;
        foreach ($files as $file) {
            if (strpos($file->getFilename(), 'chunk_') === 0) {
                $uploadedChunks++;
            }
        }

        if ($uploadedChunks === $totalChunks) {
            // Merge all chunks
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $extension = $extension ? '.' . $extension : '.png';
            $finalFileName = $fileId . $extension;
            
            $parentTempDir = storage_path('app/temp_uploads');
            if (!File::exists($parentTempDir)) {
                File::makeDirectory($parentTempDir, 0755, true, true);
            }
            
            $finalPath = $parentTempDir . '/' . $finalFileName;

            $out = fopen($finalPath, 'wb');
            if ($out === false) {
                return response()->json(['error' => 'Failed to open output stream.'], 500);
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/chunk_' . $i;
                if (!File::exists($chunkPath)) {
                    fclose($out);
                    return response()->json(['error' => 'Missing chunk: ' . $i], 400);
                }

                $in = fopen($chunkPath, 'rb');
                if ($in === false) {
                    fclose($out);
                    return response()->json(['error' => 'Failed to open chunk stream: ' . $i], 500);
                }

                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }

                fclose($in);
            }

            fclose($out);

            // Delete temporary chunk folder
            File::deleteDirectory($tempDir);

            return response()->json([
                'success' => true,
                'temp_file_name' => $finalFileName,
                'message' => 'Upload complete'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chunk uploaded successfully'
        ]);
    }
}
