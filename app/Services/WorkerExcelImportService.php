<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WorkerExcelImportService
{
    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $header = $this->normalizeHeader(array_shift($rows) ?: []);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            $data = $this->rowData($row, $header);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $email = Str::lower(trim((string) ($data['email'] ?? '')));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $errors[] = "Ligne {$rowNumber}: e-mail invalide ou manquant.";

                continue;
            }

            $name = trim((string) ($data['name'] ?? ''));
            $nom = trim((string) ($data['nom'] ?? ''));
            $prenom = trim((string) ($data['prenom'] ?? ''));
            $fullName = $name !== '' ? $name : trim($prenom.' '.$nom);

            if ($fullName === '') {
                $skipped++;
                $errors[] = "Ligne {$rowNumber}: nom complet manquant.";

                continue;
            }

            $payload = [
                'name' => $fullName,
                'nom' => $nom !== '' ? $nom : null,
                'postnom' => $this->nullable($data['postnom'] ?? null),
                'prenom' => $prenom !== '' ? $prenom : null,
                'sexe' => $this->normalizeSexe($data['sexe'] ?? null),
                'date_naissance' => $this->nullable($data['date_naissance'] ?? null),
                'email' => $email,
                'indicatif_telephone' => $this->nullable($data['indicatif_telephone'] ?? '+243'),
                'telephone' => $this->nullable($data['telephone'] ?? null),
                'telephone_urgence' => $this->nullable($data['telephone_urgence'] ?? null),
                'guardian_name' => $this->nullable($data['guardian_name'] ?? null),
                'guardian_phone' => $this->nullable($data['guardian_phone'] ?? null),
                'adresse' => $this->nullable($data['adresse'] ?? null),
                'commune' => $this->nullable($data['commune'] ?? null),
                'ville' => $this->nullable($data['ville'] ?? null),
                'eglise_assemblee' => $this->nullable($data['eglise_assemblee'] ?? null),
                'departement_cellule' => $this->nullable($data['departement_cellule'] ?? null),
                'hebergement_choice' => $this->nullable($data['hebergement_choice'] ?? null),
                'role_jeunesse' => $this->nullable($data['role_jeunesse'] ?? null) ?: 'Ouvrier',
                'fonction_metier' => $this->nullable($data['fonction_metier'] ?? null) ?: 'ouvrier',
                'is_active' => $this->boolean($data['is_active'] ?? true),
            ];

            $user = User::query()->where('email', $email)->first();
            $exists = (bool) $user;

            if (! $user) {
                $user = new User;
                $user->password = Hash::make(Str::random(16));
            }

            $user->fill($payload);
            $user->save();

            $exists ? $updated++ : $created++;
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    public function createTemplate(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ouvriers');

        $headers = $this->headers();
        foreach ($headers as $index => $header) {
            $cell = chr(65 + $index).'1';
            $sheet->setCellValue($cell, $header);
        }

        $example = [
            'Nom complet', 'Kabongo', 'Mbuyi', 'Jean', 'M', '1998-05-12', 'jean.kabongo@example.com',
            '+243', '891234567', '899000111', 'Marie Kabongo', '898000111', '12 avenue Exemple',
            'Gombe', 'Kinshasa', 'CMP Gombe', 'Jeunesse', 'interne', 'Ouvrier', 'ouvrier', '1',
        ];

        foreach ($example as $index => $value) {
            $sheet->setCellValueExplicit(chr(65 + $index).'2', $value, DataType::TYPE_STRING);
        }

        $lastColumn = chr(65 + count($headers) - 1);
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F5E9');
        $sheet->freezePane('A2');

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($path);
    }

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'name',
            'nom',
            'postnom',
            'prenom',
            'sexe',
            'date_naissance',
            'email',
            'indicatif_telephone',
            'telephone',
            'telephone_urgence',
            'guardian_name',
            'guardian_phone',
            'adresse',
            'commune',
            'ville',
            'eglise_assemblee',
            'departement_cellule',
            'hebergement_choice',
            'role_jeunesse',
            'fonction_metier',
            'is_active',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    protected function normalizeHeader(array $row): array
    {
        $header = [];
        foreach ($row as $column => $value) {
            $header[$column] = Str::of((string) $value)->trim()->lower()->replace([' ', '-'], '_')->toString();
        }

        return $header;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $header
     * @return array<string, mixed>
     */
    protected function rowData(array $row, array $header): array
    {
        $data = [];
        foreach ($header as $column => $name) {
            if ($name === '') {
                continue;
            }

            $data[$name] = is_string($row[$column] ?? null) ? trim((string) $row[$column]) : ($row[$column] ?? null);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function isEmptyRow(array $data): bool
    {
        return collect($data)->filter(fn ($value): bool => filled($value))->isEmpty();
    }

    protected function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeSexe(mixed $value): ?string
    {
        $value = Str::lower(trim((string) $value));

        return match ($value) {
            'm', 'masculin', 'homme' => 'M',
            'f', 'feminin', 'féminin', 'femme' => 'F',
            default => $value === '' ? null : (string) $value,
        };
    }

    protected function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'oui', 'yes', 'actif'], true);
    }
}
