<?php

namespace App\Filament\Resources\GeneratedQrCodes\Schemas;

use App\Services\QrCode\QrCodeGeneratorService;
use App\Support\QrCodeLogoCatalog;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Formulaire de création / édition d'un QR code avec aperçu en direct.
 */
class GeneratedQrCodeForm
{
    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paramètres du QR code')
                    ->description('Renseignez le lien et le logo avant de générer le fichier PNG.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->helperText('Libellé interne (ex. Flyer inscription 2026, Don retraite).')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('target_url')
                            ->label('Lien cible')
                            ->helperText('URL complète encodée dans le QR code (https://…).')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Toggle::make('embed_logo')
                            ->label('Afficher un logo au centre')
                            ->helperText('Superpose le logo choisi au centre du QR code.')
                            ->default(false)
                            ->live(),
                        Select::make('logo_key')
                            ->label('Logo au centre')
                            ->options(QrCodeLogoCatalog::selectOptions())
                            ->default(QrCodeLogoCatalog::KEY_JEUNESSE)
                            ->required(fn (Get $get): bool => (bool) $get('embed_logo'))
                            ->visible(fn (Get $get): bool => (bool) $get('embed_logo'))
                            ->live(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Aperçu avant enregistrement')
                    ->description('Visualisation immédiate du QR code selon les paramètres ci-dessus.')
                    ->schema([
                        Html::make(function (Get $get): HtmlString {
                            $url = trim((string) $get('target_url'));
                            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                                return new HtmlString(
                                    '<div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-sm text-gray-500">'
                                    .'Saisissez un lien valide pour afficher l’aperçu du QR code.'
                                    .'</div>'
                                );
                            }

                            try {
                                $png = app(QrCodeGeneratorService::class)->buildPngBinary(
                                    $url,
                                    (bool) $get('embed_logo'),
                                    (string) ($get('logo_key') ?: QrCodeLogoCatalog::KEY_JEUNESSE)
                                );
                                $b64 = base64_encode($png);

                                return new HtmlString(
                                    '<div class="flex flex-col items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6">'
                                    .'<img src="data:image/png;base64,'.$b64.'" alt="Aperçu QR code" style="max-width:320px;width:100%;height:auto;border-radius:12px;" />'
                                    .'<p class="text-xs text-gray-500 break-all text-center max-w-md">'.e($url).'</p>'
                                    .'</div>'
                                );
                            } catch (\Throwable) {
                                return new HtmlString(
                                    '<div class="rounded-xl border border-danger-300 p-6 text-center text-sm text-danger-600">'
                                    .'Impossible de générer l’aperçu. Vérifiez le lien et le logo sélectionné.'
                                    .'</div>'
                                );
                            }
                        }),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
