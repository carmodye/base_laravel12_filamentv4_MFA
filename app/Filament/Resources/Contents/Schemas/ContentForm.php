<?php

namespace App\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $orgOptions = $user ? $user->organizations()->pluck('name', 'organizations.id')->toArray() : [];
        $isEdit = request()->routeIs('filament.resources.contents.edit');
        // Only allow organization change on edit, all fields on create (but create is disabled)
        return $schema->components([
            Select::make('organization_id')
                ->options($orgOptions)
                ->required(),
        ]);
    }
}
