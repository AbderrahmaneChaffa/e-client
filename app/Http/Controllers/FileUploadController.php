<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function showForm()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        // Validate single file
        $request->validate([
            'file' => 'nullable|file|max:2048', // Max 2MB
            'files.*' => 'nullable|file|max:2048', // Max 2MB per file
        ]);

        // Handle single file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads', $fileName, 'public');
        }

        // Handle multiple file uploads
        if ($request->hasFiles('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('uploads', $fileName, 'public');
            }
        }

        return back()->with('success', 'Files uploaded successfully!');
    }
}
