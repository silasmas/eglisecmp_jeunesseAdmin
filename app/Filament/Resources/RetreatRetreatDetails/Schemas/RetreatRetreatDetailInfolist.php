<?php

namespace App\Filament\Resources\RetreatRetreatDetails\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RetreatRetreatDetailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('theme'),
                TextEntry::make('speaker'),
                TextEntry::make('notes')
                    ->columnSpanFull(),
                TextEntry::make('event.name')
                    ->label('Event')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
