<?php

namespace App\Filament\Resources\RetreatPolicies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RetreatPolicyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event.name')
                    ->label('Event')
                    ->placeholder('-'),
                TextEntry::make('category'),
                TextEntry::make('title'),
                TextEntry::make('content')
                    ->columnSpanFull(),
                TextEntry::make('target_audience'),
                TextEntry::make('severity_level')
                    ->numeric(),
                IconEntry::make('is_mandatory')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('effective_from')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('effective_to')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
