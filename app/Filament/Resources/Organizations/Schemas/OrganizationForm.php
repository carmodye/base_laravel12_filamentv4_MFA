<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Models\Organization;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->rules(function ($get) {
                        $rules = [];
                        if ($get('parent_id') === null) {
                            $rules[] = Rule::unique('organizations', 'name')->whereNull('parent_id');
                        }
                        return $rules;
                    }),
                Select::make('parent_id')
                    ->label('Parent Organization')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
