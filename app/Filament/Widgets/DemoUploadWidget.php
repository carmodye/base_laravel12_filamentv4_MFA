<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DemoUploadWidget extends Widget
{
    use WithFileUploads;

    public $file;
    public $status;
    public $errors = [];

    protected string $view = 'filament.widgets.demo-upload-widget';

    public function upload()
    {
        Log::info('Upload method called');
        $this->resetErrorBag();
        $this->status = null;
        try {
            $user = auth()->user();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }
            $this->validate([
                'file' => [
                    'required',
                    'file',
                    'max:51200',
                    'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/webm',
                ],
            ]);

            Log::info('Validation passed');
            $user = auth()->user();
            $rootOrg = $this->getRootOrganization($user->organizations()->first());
            Log::info('User: ' . $user->id . ', Root Org: ' . ($rootOrg ? $rootOrg->id : 'null'));
            $rootDir = $rootOrg ? Str::slug($rootOrg->name) : 'unassigned';

            $file = $this->file;
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . ($extension ? ".{$extension}" : '');
            $path = "uploads/{$rootDir}/{$user->id}/" . now()->format('Y/m/d');

            $storedPath = Storage::disk('public')->putFileAs($path, $file, $filename, [
                'visibility' => 'private',
            ]);

            Log::info('File uploaded to S3: ' . $storedPath);

            // Extract metadata
            $metadata = [];
            $width = null;
            $height = null;
            $aspectRatio = null;
            if (str_starts_with($file->getMimeType(), 'image/')) {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    $aspectRatio = $width / $height;
                }
            }
            // For videos, we can add FFmpeg later

            Content::create([
                'user_id' => $user->id,
                'organization_id' => $rootOrg?->id,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => $aspectRatio,
                'metadata' => $metadata,
            ]);

            Log::info('Content record created');
            $this->status = "Uploaded and saved to database";
            $this->file = null;
        } catch (ValidationException $e) {
            Log::error('Validation error: ' . $e->getMessage());
            $this->errors = $e->validator->errors()->all();
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            $this->errors = [$e->getMessage()];
        }
    }

    private function getRootOrganization(?Organization $organization): ?Organization
    {
        if (! $organization) {
            return null;
        }
        $current = $organization;
        while ($current->parent) {
            $current = $current->parent;
        }
        return $current;
    }
}
