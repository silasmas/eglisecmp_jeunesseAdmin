<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatParticipantMovementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail mouvement')
                    ->schema([
                        ImageEntry::make('participant.photo')
                            ->label('Photo participant')
                            ->circular()
                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode((string) $record->participant?->full_name).'&background=7b1d3e&color=fff'),
                        TextEntry::make('participant.full_name')
                            ->label('Participant'),
                        TextEntry::make('event.name')
                            ->label('Evenement'),
                        TextEntry::make('movement_type')
                            ->label('Type')
                            ->badge(),
                        TextEntry::make('moved_at')
                            ->label('Date / heure')
                            ->dateTime(),
                        TextEntry::make('authorizedBy.name')
                            ->label('Autorise par')
                            ->placeholder('-'),
                        TextEntry::make('reason')
                            ->label('Motif')
                            ->placeholder('-'),
                        TextEntry::make('note')
                            ->label('Observation')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
