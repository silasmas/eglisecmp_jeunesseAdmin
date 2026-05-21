<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Schemas;

use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatActivityAttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pointage activite')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('activity_plan_id')
                            ->label('Activite')
                            ->relationship('activityPlan', 'title')
                            ->searchable()
                            ->required(),
                        UserSelect::make('participant_ids')
                            ->label('Participants (selection multiple)')
                            ->getSearchResultsUsing(fn (string $search): array => app(RetreatAtelierAuthorizationService::class)
                                ->scopeParticipantsForUser(RetreatParticipant::query(), auth()->user())
                                ->where(function ($query) use ($search): void {
                                    $query
                                        ->where('prenom', 'like', "%{$search}%")
                                        ->orWhere('nom', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%")
                                        ->orWhere('telephone', 'like', "%{$search}%");
                                })
                                ->orderBy('prenom')
                                ->orderBy('nom')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (RetreatParticipant $participant): array => [
                                    $participant->id => view('user-fields::user-avatar-option', ['user' => $participant])->render(),
                                ])
                                ->all())
                            ->getOptionLabelsUsing(fn (array $values): array => RetreatParticipant::query()
                                ->whereIn('id', $values)
                                ->get()
                                ->mapWithKeys(fn (RetreatParticipant $participant): array => [
                                    $participant->id => view('user-fields::user-avatar-option', ['user' => $participant])->render(),
                                ])
                                ->all())
                            ->allowHtml()
                            ->helperText('Choisis plusieurs participants a enregistrer en une seule operation.')
                            ->multiple()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->searchable()
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(),
                        UserSelect::make('participant_id')
                            ->label('Participant')
                            ->relationship('participant', 'nom')
                            ->searchable()
                            ->visible(fn (string $operation): bool => $operation !== 'create')
                            ->required(fn (string $operation): bool => $operation !== 'create'),
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'En retard',
                                'excused' => 'Excuse',
                            ])
                            ->required()
                            ->default('absent'),
                        TimePicker::make('check_in_at')
                            ->label("Heure d'entree")
                            ->seconds(false),
                        TimePicker::make('check_out_at')
                            ->label('Heure de sortie')
                            ->seconds(false),
                        Select::make('scan_source')
                            ->label('Source pointage')
                            ->options([
                                'manual' => 'Manuel',
                                'qr' => 'QR code',
                                'nfc' => 'NFC',
                            ])
                            ->required()
                            ->default('manual'),
                        UserSelect::make('recorded_by')
                            ->label('Enregistre par')
                            ->relationship('recorder', 'name')
                            ->default(fn (): ?int => Auth::id())
                            ->searchable()
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('note')
                            ->label('Note')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Pointage actif')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
