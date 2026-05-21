<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UsersStats;
use App\Services\WorkerExcelImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_worker_template')
                ->label('Modele Excel ouvriers')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (WorkerExcelImportService $importer): BinaryFileResponse {
                    $directory = storage_path('app/import-templates');
                    if (! is_dir($directory)) {
                        mkdir($directory, 0775, true);
                    }

                    $path = $directory.'/modele-import-ouvriers.xlsx';
                    $importer->createTemplate($path);

                    return response()->download($path, 'modele-import-ouvriers.xlsx');
                }),
            Action::make('import_workers')
                ->label('Importer ouvriers')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Importer les ouvriers depuis Excel')
                ->modalDescription('Utilisez le fichier modele pour eviter les erreurs de colonnes. Les comptes existants sont mis a jour par e-mail.')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')
                        ->directory('imports/workers')
                        ->required(),
                ])
                ->action(function (array $data, WorkerExcelImportService $importer): void {
                    $uploaded = $data['file'] ?? '';
                    $relativePath = is_array($uploaded) ? (string) reset($uploaded) : (string) $uploaded;
                    $path = Storage::disk('local')->path($relativePath);
                    $result = $importer->import($path);

                    $message = "{$result['created']} cree(s), {$result['updated']} mis a jour, {$result['skipped']} ignore(s).";
                    if ($result['errors'] !== []) {
                        $message .= ' Erreurs: '.implode(' | ', array_slice($result['errors'], 0, 5));
                    }

                    Notification::make()
                        ->title('Import ouvriers termine')
                        ->body($message)
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UsersStats::class,
        ];
    }
}
