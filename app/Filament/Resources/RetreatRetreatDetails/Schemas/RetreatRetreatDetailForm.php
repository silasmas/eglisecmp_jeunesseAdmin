<?php

namespace App\Filament\Resources\RetreatRetreatDetails\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RetreatRetreatDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('theme')
                    ->required(),
                TextInput::make('speaker')
                    ->required(),
                Textarea::make('notes')
                    ->required()
                    ->columnSpanFull(),
                Select::make('event_id')
                    ->relationship('event', 'name'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
