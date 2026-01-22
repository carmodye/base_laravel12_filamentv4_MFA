<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $relatedResource = null;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('email'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->form(fn ($form) => $form
                        ->schema([
                            Select::make('recordId')
                                ->label('User')
                                ->options(User::all()->mapWithKeys(fn ($user) => [$user->id => "{$user->name} ({$user->email})"]))
                                ->searchable()
                                ->required(),
                        ])
                    ),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
