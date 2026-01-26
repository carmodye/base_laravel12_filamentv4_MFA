<?php

namespace App\Filament\Resources\Contents;

use App\Filament\Resources\Contents\Pages\CreateContent;
use App\Filament\Resources\Contents\Pages\EditContent;
use App\Filament\Resources\Contents\Pages\ListContents;
use App\Filament\Resources\Contents\Schemas\ContentForm;
use App\Filament\Resources\Contents\Tables\ContentsTable;
use App\Models\Content;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'filename';

    public static function form(Schema $schema): Schema
    {
        return ContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentsTable::configure($table)
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('process')
                    ->label('Process with FFmpeg')
                    ->action(function (Content $record) {
                        ProcessContentWithFfmpeg::dispatch($record);
                    })
                    ->visible(fn (Content $record) => !$record->trashed()),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Content $record) {
                        Log::info('Restore action triggered for record ID: ' . $record->id);
                        try {
                            Log::info('Starting restore for record ID: ' . $record->id . ', trashed: ' . ($record->trashed() ? 'yes' : 'no'));
                            $result = $record->restore();
                            Log::info('Restore result: ' . ($result ? 'true' : 'false') . ' for record ID: ' . $record->id);
                            Log::info('After restore, trashed: ' . ($record->trashed() ? 'yes' : 'no'));
                            \Filament\Notifications\Notification::make()
                                ->title('Record restored successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Error restoring record: ' . $e->getMessage());
                        }
                    })
                    ->visible(fn (Content $record) => $record->trashed()),
                Action::make('delete')
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->action(function (Content $record) {
                        $record->delete();
                    })
                    ->visible(fn (Content $record) => !$record->trashed()),
                Action::make('force_delete')
                    ->label('Force Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Content $record) {
                        $record->forceDelete();
                        \Filament\Notifications\Notification::make()
                            ->title('Record permanently deleted')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Content $record) => $record->trashed()),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        // If super_admin, show all content
        if (! $user->hasRole(config('filament-shield.super_admin.name', 'super_admin'))) {
            $orgIds = $user->organizations()->pluck('organizations.id');
            $query = $query->whereIn('organization_id', $orgIds);
        }
        // Debug: check request structure
        // dd(request()->all());
        // Use correct filter key for trashed filter
        $trashed = request()->input('tableFilters.trashed');
        if ($trashed === 'only') {
            $query = $query->onlyTrashed();
        } elseif ($trashed === 'with') {
            $query = $query->withTrashed();
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            //'create' => CreateContent::route('/create'),
            //'edit' => EditContent::route('/{record}/edit'),
        ];
    }
}