<?php

namespace App\Filament\Resources\RetreatParticipants\Schemas;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use App\Models\RetreatParticipant;
use App\Services\PublicStorageUrl;
use App\Filament\Support\RetreatBilletPreviewFilamentAction;
use App\Filament\Support\RetreatPaymentProofFilamentAction;
use App\Support\RetreatBilletPageBuilder;
use App\Support\RetreatInscriptionResumeUrl;
use App\Support\RetreatParticipantPaymentProof;
use App\Services\RetreatInscriptionFunnelService;
use App\Support\AvatarFallback;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RetreatParticipantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Photo du participant')
                    ->schema([
                        ImageEntry::make('photo')
                            ->label('Photo')
                            ->height(220)
                            ->columnSpanFull()
                            ->defaultImageUrl(fn (): string => AvatarFallback::url())
                            ->getStateUsing(
                                fn (RetreatParticipant $record): ?string => app(PublicStorageUrl::class)->fromPath($record->photo),
                            )
                            ->placeholder('Aucune photo enregistree'),
                    ]),
                Section::make('Identite')
                    ->schema([
                        TextEntry::make('nom'),
                        TextEntry::make('prenom'),
                        TextEntry::make('age')->numeric(),
                        TextEntry::make('email')->label('Email')->placeholder('-'),
                        TextEntry::make('sexe')->placeholder('-'),
                        TextEntry::make('telephone')->placeholder('-'),
                        TextEntry::make('adresse')->placeholder('-'),
                        TextEntry::make('telephone_urgence')->label('Tel urgence')->placeholder('-'),
                        TextEntry::make('observation')->placeholder('-')->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Rattachement')
                    ->schema([
                        TextEntry::make('user.name')->label('Utilisateur')->placeholder('-'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('-'),
                        TextEntry::make('atelier_id')->numeric()->placeholder('-'),
                        TextEntry::make('chambre_id')->numeric()->placeholder('-'),
                        TextEntry::make('role_participant'),
                        TextEntry::make('participant_type'),
                        TextEntry::make('qr_code')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Paiement et billet')
                    ->schema([
                        TextEntry::make('preuve_paiement')
                            ->label('Preuve paiement')
                            ->placeholder('—')
                            ->formatStateUsing(fn (?string $state, RetreatParticipant $record): string => RetreatParticipantPaymentProof::hasViewableProof($record) ? 'Fichier disponible' : '—')
                            ->suffixAction(
                                RetreatPaymentProofFilamentAction::make('voir_preuve_paiement')
                                    ->label('Consulter')
                            ),
                        IconEntry::make('paiement_valide')->boolean(),
                        TextEntry::make('resume_inscription_url')
                            ->label('Lien reprise paiement')
                            ->placeholder('—')
                            ->copyable()
                            ->copyMessage('Lien copié')
                            ->state(fn (RetreatParticipant $record): ?string => RetreatInscriptionResumeUrl::urlForParticipant($record))
                            ->url(fn (RetreatParticipant $record): ?string => RetreatInscriptionResumeUrl::urlForParticipant($record))
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Copier / ouvrir le lien de reprise' : '—')
                            ->helperText('À envoyer au participant pour qu’il continue son inscription à l’étape paiement.')
                            ->visible(fn (RetreatParticipant $record): bool => RetreatInscriptionResumeUrl::canResumeForParticipant($record)),
                        TextEntry::make('download_token')
                            ->label('Lien billet public')
                            ->placeholder('—')
                            ->copyable()
                            ->copyMessage('Lien copié')
                            ->state(fn (RetreatParticipant $record): ?string => RetreatBilletPageBuilder::publicUrl($record))
                            ->url(fn (RetreatParticipant $record): ?string => RetreatBilletPageBuilder::publicUrl($record))
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Ouvrir le billet public' : '—')
                            ->visible(fn (RetreatParticipant $record): bool => (bool) $record->paiement_valide),
                        TextEntry::make('billet_preview')
                            ->label('Aperçu billet (admin)')
                            ->formatStateUsing(fn (): string => 'Prévisualiser la page billet')
                            ->url(fn (RetreatParticipant $record): ?string => RetreatBilletPageBuilder::adminPreviewUrl($record))
                            ->openUrlInNewTab()
                            ->color('info')
                            ->icon('heroicon-o-ticket')
                            ->visible(fn (RetreatParticipant $record): bool => RetreatBilletPreviewFilamentAction::canPreview($record)),
                        TextEntry::make('sponsorshipVoucher.code')
                            ->label('Code prise en charge')
                            ->badge()
                            ->color('info')
                            ->copyable()
                            ->copyMessage('Code copié')
                            ->copyMessageDuration(2000)
                            ->visible(fn (RetreatParticipant $record): bool => filled($record->sponsorshipVoucher?->code)),
                        TextEntry::make('sponsorshipVoucher.donation.reference')
                            ->label('Référence don parrain')
                            ->badge()
                            ->color('gray')
                            ->copyable()
                            ->url(fn (RetreatParticipant $record): ?string => $record->sponsorshipVoucher?->donation_id
                                ? RetreatVoluntaryDonationResource::getUrl('view', ['record' => $record->sponsorshipVoucher->donation_id])
                                : null)
                            ->visible(fn (RetreatParticipant $record): bool => filled($record->sponsorshipVoucher?->donation_id)),
                        TextEntry::make('sponsorshipVoucher.donation.donor_name')
                            ->label('Donateur parrain')
                            ->placeholder('—')
                            ->visible(fn (RetreatParticipant $record): bool => filled($record->sponsorshipVoucher?->donation_id)),
                        TextEntry::make('sponsorshipVoucher.redeemed_at')
                            ->label('Code utilisé le')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn (RetreatParticipant $record): bool => filled($record->sponsorshipVoucher?->redeemed_at)),
                        IconEntry::make('billet_envoye')->boolean(),
                        TextEntry::make('date_billet_envoye')->dateTime()->placeholder('-'),
                        TextEntry::make('billet_pdf')->placeholder('-'),
                        IconEntry::make('billet_envoye_email')->boolean(),
                        IconEntry::make('billet_envoye_whatsapp')->boolean(),
                        IconEntry::make('badge_received')
                            ->label('Badge remis')
                            ->boolean()
                            ->visible(fn ($record): bool => (bool) $record->paiement_valide),
                        TextEntry::make('badge_received_at')
                            ->label('Badge remis le')
                            ->dateTime()
                            ->placeholder('En attente de remise')
                            ->visible(fn ($record): bool => (bool) $record->paiement_valide),
                        TextEntry::make('badgeReceivedBy.name')
                            ->label('Badge remis par')
                            ->placeholder('-')
                            ->visible(fn ($record): bool => (bool) $record->paiement_valide),
                    ])
                    ->columns(2),
                Section::make('Presence et inscription')
                    ->schema([
                        IconEntry::make('present')->boolean(),
                        TextEntry::make('date_presence')->dateTime()->placeholder('-'),
                        TextEntry::make('retreatAccessGrantedBy.name')
                            ->label('Acces retraite par')
                            ->placeholder('-'),
                        IconEntry::make('exit_allowed')->boolean(),
                        TextEntry::make('curfew_time')->time()->placeholder('-'),
                        TextEntry::make('guardian_name')->placeholder('-'),
                        TextEntry::make('guardian_phone')->placeholder('-'),
                        TextEntry::make('inscription_funnel_stage')
                            ->label('Étape parcours inscription')
                            ->formatStateUsing(
                                fn (?string $state): string => app(RetreatInscriptionFunnelService::class)->labelFor($state)
                            )
                            ->placeholder('—'),
                        TextEntry::make('inscription_funnel_detail')
                            ->label('Détail parcours')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('inscription_funnel_at')
                            ->label('Dernière activité parcours')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('registration_status'),
                        TextEntry::make('registration_otp_code')->placeholder('-'),
                        TextEntry::make('registration_otp_sent_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_expires_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_verified_at')->dateTime()->placeholder('-'),
                        TextEntry::make('registration_otp_attempts')->numeric(),
                        IconEntry::make('is_verified')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
