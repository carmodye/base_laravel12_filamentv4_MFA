<?php

namespace App\Jobs;

use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessContentWithFfmpeg implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Content $content,
        public array $options = []
    ) {}

    public function handle(): void
    {
        $filePath = Storage::disk('s3')->url($this->content->path);
        // For simplicity, assume local temp file or use S3 URL if FFmpeg supports
        // This is a placeholder; actual implementation depends on setup

        // Example: for images, create a thumbnail
        if (str_starts_with($this->content->mime_type, 'image/')) {
            // Use FFmpeg to resize
            $thumbnailPath = str_replace($this->content->filename, 'thumb_' . $this->content->filename, $this->content->path);
            // exec("ffmpeg -i {$filePath} -vf scale=300:-1 {$thumbnailPath}"); // Adjust for S3
            // Then upload to S3
        }

        // Update metadata
        $this->content->update(['metadata' => array_merge($this->content->metadata ?? [], ['processed' => true])]);
    }
}
