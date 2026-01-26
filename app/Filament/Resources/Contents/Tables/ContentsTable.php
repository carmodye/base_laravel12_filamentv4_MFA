<?php

namespace App\Filament\Resources\Contents\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Log;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Organization;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->searchable(),
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
                            ->disk('s3'),
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
                        $originalName = basename($filePath);
                        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                        // Download to temp for processing
                        $tempPath = tempnam(sys_get_temp_dir(), 'upload');
                        file_put_contents($tempPath, $disk->get($filePath));
                        $rootOrg = self::getRootOrganization($user->organizations()->first());
                        $rootDir = $rootOrg ? Str::slug($rootOrg->name) : 'unassigned';
                        $filename = Str::uuid() . ($extension ? ".{$extension}" : '');
                        $path = "uploads/{$rootDir}/{$user->id}/" . now()->format('Y/m/d');
                        $storedPath = $path . '/' . $filename;
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
                        Content::create([
                            'user_id' => $user->id,
                            'organization_id' => $rootOrg?->id,
                            'filename' => $filename,
                            'original_name' => $originalName,
                            'path' => $storedPath,
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'width' => $width,
                            'height' => $height,
                            'aspect_ratio' => $aspectRatio,
                            'metadata' => $metadata,
                        ]);
                        Log::info('Content created via action');
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('process')
                    ->label('Process with FFmpeg')
                    ->action(function (Content $record) {
                        ProcessContentWithFfmpeg::dispatch($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
