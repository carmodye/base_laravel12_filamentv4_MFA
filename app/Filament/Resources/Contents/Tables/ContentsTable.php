<?php

namespace App\Filament\Resources\Contents\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Content;
use App\Models\Organization;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;

class ContentsTable
{
    public static function configure(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('organization.name')
                    ->searchable(),
                TextColumn::make('filename')
                    ->searchable(),
                TextColumn::make('original_name')
                    ->searchable(),
                TextColumn::make('path')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('s3_url')
                    ->label('S3 Link')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => $record->s3_url)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn ($record) => $record->s3_url ? '' : null),
                TextColumn::make('mime_type')
                    ->searchable(),
                TextColumn::make('size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('width')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('height')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('aspect_ratio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload File')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->form([
                        FileUpload::make('file')
                            ->label('Image or video')
                            ->required()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm'])
                            ->maxSize(51200)
                            ->disk('s3')
                            ->preserveFilenames(),
                    ])
                    ->action(function (array $data) {
                        Log::info('Upload action called');
                        $user = auth()->user();
                        if (!$user) {
                            throw new \Exception('User not authenticated');
                        }
                        $filePath = $data['file'];
                        $disk = Storage::disk('s3');
                        if (!$disk->exists($filePath)) {
                            throw new \Exception("Uploaded file not found at {$filePath} in S3");
                        }
                        $mimeType = $disk->mimeType($filePath);
                        $size = $disk->size($filePath);
                        // Always use the user's original filename if available
                        $originalName = null;
                        if (isset($_FILES) && is_array($_FILES)) {
                            foreach ($_FILES as $file) {
                                if (isset($file['name']) && !empty($file['name'])) {
                                    $originalName = $file['name'];
                                    break;
                                }
                            }
                        }
                        if (!$originalName && is_object($data['file']) && method_exists($data['file'], 'getClientOriginalName')) {
                            $originalName = $data['file']->getClientOriginalName();
                        }
                        if (!$originalName) {
                            // Fallback: try to get the last part after the last '/'
                            $parts = explode('/', $filePath);
                            $last = end($parts);
                            $originalName = $last;
                        }
                        $filename = $originalName;
                        // Download to temp for processing
                        $tempPath = tempnam(sys_get_temp_dir(), 'upload');
                        file_put_contents($tempPath, $disk->get($filePath));
                        $rootOrg = self::getRootOrganization($user->organizations()->first());
                        $orgSlug = $rootOrg ? Str::slug($rootOrg->name) : 'unassigned';
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $baseName = pathinfo($filename, PATHINFO_FILENAME);
                        $timestamp = time();
                        $storedPath = "{$orgSlug}/{$baseName}{$timestamp}" . ($extension ? ".{$extension}" : '');
                        $disk->move($filePath, $storedPath);
                        // Extract metadata
                        $metadata = [];
                        $width = null;
                        $height = null;
                        $aspectRatio = null;
                        if (str_starts_with($mimeType, 'image/')) {
                            $imageInfo = getimagesize($tempPath);
                            if ($imageInfo) {
                                $width = $imageInfo[0];
                                $height = $imageInfo[1];
                                $aspectRatio = $width / $height;
                            }
                        }
                        unlink($tempPath);
                        $s3Url = $disk->url($storedPath);
                        $content = Content::create([
                            'user_id' => $user->id,
                            'organization_id' => $rootOrg?->id,
                            'filename' => basename($storedPath), // S3 object name
                            'original_name' => $filename, // user's original filename
                            'path' => $storedPath, // S3 path
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'width' => $width,
                            'height' => $height,
                            'aspect_ratio' => $aspectRatio,
                            'metadata' => $metadata,
                            's3_url' => $s3Url,
                        ]);
                        Log::info('S3 File URL: ' . $s3Url);
                        Log::info('Content created via action');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Action::make('restore')
                        ->label('Restore')
                        ->requiresConfirmation()
                        ->accessSelectedRecords()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (method_exists($record, 'restore')) {
                                    $record->restore();
                                }
                            }
                        })
                        ->visible(fn ($records) => collect($records)->filter(fn ($r) => method_exists($r, 'trashed') && $r->trashed())->count() > 0),
                ])
            ]);
    }

    private static function getRootOrganization(?Organization $organization): ?Organization
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
