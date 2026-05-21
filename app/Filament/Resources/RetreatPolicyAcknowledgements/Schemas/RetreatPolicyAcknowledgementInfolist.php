<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RetreatPolicyAcknowledgementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('policy.title')
                    ->label('Policy'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('participant.id')
                    ->label('Participant')
                    ->placeholder('-'),
                IconEntry::make('has_read')
                    ->boolean(),
                IconEntry::make('has_accepted')
                    ->boolean(),
                TextEntry::make('acknowledged_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('signature_type'),
                TextEntry::make('ip_address')
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
