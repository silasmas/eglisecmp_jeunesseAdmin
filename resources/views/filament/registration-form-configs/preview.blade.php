@php
    use App\Support\RegistrationFormPreviewBuilder;
    use App\Support\RegistrationFormUiSettings;
    use App\Enums\RegistrationFormFieldType;

    $stepLabels = RegistrationFormPreviewBuilder::stepOptions();
    $uiBlocks = $uiBlocks ?? [];
    $visibleFieldCount = collect($fields)->where('is_visible', true)->count();

    $worker = $uiBlocks['worker'] ?? null;
    $parent = $uiBlocks['parent'] ?? null;
    $paymentModes = $uiBlocks['payment_modes'] ?? [];

    $workerVisible = $worker && ($worker['is_visible'] ?? false);
    $parentVisible = $parent && ($parent['is_visible'] ?? false);
    $workerBefore = ($worker['position'] ?? RegistrationFormUiSettings::POSITION_BEFORE_FIELDS) === RegistrationFormUiSettings::POSITION_BEFORE_FIELDS;
    $parentBefore = ($parent['position'] ?? RegistrationFormUiSettings::POSITION_BEFORE_FIELDS) === RegistrationFormUiSettings::POSITION_BEFORE_FIELDS;
    $visiblePaymentCount = collect($paymentModes)->where('is_visible', true)->count();

    $visibleCount = match ($step) {
        0 => $visibleFieldCount + ($workerVisible ? 1 : 0),
        1 => $visibleFieldCount + ($parentVisible ? 1 : 0),
        4 => $visiblePaymentCount,
        default => $visibleFieldCount,
    };
@endphp

<div class="reg-form-preview">
    <style>
        .reg-form-preview {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.98));
        }
        .dark .reg-form-preview {
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.55), rgba(15, 23, 42, 0.75));
            border-color: rgba(100, 116, 139, 0.45);
        }
        .reg-form-preview__head {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.85rem;
        }
        .reg-form-preview__title {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
        }
        .reg-form-preview__meta {
            font-size: 0.75rem;
            opacity: 0.75;
        }
        .reg-form-preview__stack {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .reg-form-preview__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .reg-form-preview__field {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        .reg-form-preview__field--full {
            grid-column: 1 / -1;
        }
        .reg-form-preview__label {
            font-size: 0.78rem;
            font-weight: 600;
        }
        .reg-form-preview__req {
            color: #dc2626;
        }
        .reg-form-preview__opt {
            font-weight: 400;
            opacity: 0.7;
            font-size: 0.72rem;
        }
        .reg-form-preview__input {
            border: 1px dashed rgba(148, 163, 184, 0.8);
            border-radius: 8px;
            min-height: 2.1rem;
            padding: 0.45rem 0.6rem;
            font-size: 0.78rem;
            opacity: 0.85;
            background: rgba(255, 255, 255, 0.55);
        }
        .dark .reg-form-preview__input {
            background: rgba(15, 23, 42, 0.35);
        }
        .reg-form-preview__input--area {
            min-height: 3.5rem;
        }
        .reg-form-preview__input--file {
            min-height: 4.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reg-form-preview__hint {
            font-size: 0.72rem;
            opacity: 0.72;
        }
        .reg-form-preview__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.68rem;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }
        .reg-form-preview__empty {
            font-size: 0.82rem;
            opacity: 0.7;
            padding: 0.75rem 0;
        }
        .reg-form-preview__yesno {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .reg-form-preview__inline-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .reg-form-preview__inline-option {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border: 1px dashed rgba(148, 163, 184, 0.8);
            border-radius: 8px;
            font-size: 0.78rem;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.55);
        }
        .dark .reg-form-preview__inline-option {
            background: rgba(15, 23, 42, 0.35);
        }
        .reg-form-preview__inline-option--active {
            border-style: solid;
            border-color: #851c46;
            font-weight: 600;
        }
        .reg-form-preview__ui-block {
            border: 1px dashed rgba(133, 28, 70, 0.45);
            border-radius: 10px;
            padding: 0.75rem;
            background: rgba(133, 28, 70, 0.04);
        }
        .reg-form-preview__ui-tag {
            display: inline-block;
            margin-bottom: 0.45rem;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #851c46;
            opacity: 0.85;
        }
        .reg-form-preview__checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            font-size: 0.78rem;
            line-height: 1.4;
        }
        .reg-form-preview__checkbox-box {
            width: 1rem;
            height: 1rem;
            border: 1px dashed rgba(148, 163, 184, 0.9);
            border-radius: 4px;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        .reg-form-preview__info {
            font-size: 0.72rem;
            padding: 0.5rem 0.65rem;
            border-radius: 8px;
            background: rgba(251, 191, 36, 0.12);
            margin-bottom: 0.5rem;
        }
        .reg-form-preview__amount {
            text-align: center;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.65rem;
            border-radius: 999px;
            background: rgba(133, 28, 70, 0.08);
            color: #851c46;
        }
    </style>

    <div class="reg-form-preview__head">
        <div>
            <p class="reg-form-preview__title">{{ $stepLabels[$step] ?? 'Étape' }}</p>
            <p class="reg-form-preview__meta">Aperçu indicatif · {{ $visibleCount }} élément(s) visible(s)</p>
        </div>
        <span class="reg-form-preview__badge">Mise à jour en direct</span>
    </div>

    @if ($visibleCount === 0)
        <p class="reg-form-preview__empty">Aucun élément visible sur cette étape avec la configuration actuelle.</p>
    @else
        <div class="reg-form-preview__stack">
            @if ($step === 4)
                <div class="reg-form-preview__amount">Montant de l'inscription (événement actif)</div>
                <div class="reg-form-preview__field reg-form-preview__field--full">
                    <div class="reg-form-preview__label">Mode de paiement <span class="reg-form-preview__req">*</span></div>
                    <div class="reg-form-preview__inline-options">
                        @foreach ($paymentModes as $mode)
                            @continue(! ($mode['is_visible'] ?? false))
                            <span class="reg-form-preview__inline-option">{{ $mode['label'] }}</span>
                        @endforeach
                    </div>
                    <div class="reg-form-preview__hint">L'ordre et la visibilité reflètent la section « Blocs et paiement ».</div>
                </div>
            @else
                @if ($step === 0 && $workerVisible && $workerBefore)
                    @include('filament.registration-form-configs.preview-worker-block')
                @endif

                @if ($step === 1 && $parentVisible && $parentBefore)
                    @include('filament.registration-form-configs.preview-parent-block')
                @endif

                @if ($visibleFieldCount > 0)
                    <div class="reg-form-preview__grid">
                        @foreach ($fields as $field)
                            @continue(! $field['is_visible'])
                            @php
                                $type = RegistrationFormFieldType::from($field['type']);
                                $placeholder = RegistrationFormPreviewBuilder::inputPlaceholder($type);
                            @endphp
                            <div @class([
                                'reg-form-preview__field',
                                'reg-form-preview__field--full' => ($field['column_span'] ?? 'default') === 'full',
                            ])>
                                <div class="reg-form-preview__label">
                                    {{ $field['label'] }}
                                    @if ($field['is_required'])
                                        <span class="reg-form-preview__req">*</span>
                                    @else
                                        <span class="reg-form-preview__opt">(facultatif)</span>
                                    @endif
                                </div>
                                @if ($type === RegistrationFormFieldType::YesNoTextarea)
                                    <div
                                        class="reg-form-preview__yesno"
                                        x-data="{ showObservationsDetail: false }"
                                    >
                                        <div class="reg-form-preview__inline-options" role="radiogroup">
                                            <button
                                                type="button"
                                                class="reg-form-preview__inline-option"
                                                :class="{ 'reg-form-preview__inline-option--active': showObservationsDetail }"
                                                @click="showObservationsDetail = true"
                                            >Oui</button>
                                            <button
                                                type="button"
                                                class="reg-form-preview__inline-option"
                                                :class="{ 'reg-form-preview__inline-option--active': !showObservationsDetail }"
                                                @click="showObservationsDetail = false"
                                            >Non</button>
                                        </div>
                                        <div
                                            x-show="showObservationsDetail"
                                            x-cloak
                                            class="reg-form-preview__input reg-form-preview__input--area"
                                        >{{ $placeholder }}</div>
                                    </div>
                                @else
                                    <div @class([
                                        'reg-form-preview__input',
                                        'reg-form-preview__input--area' => $type === RegistrationFormFieldType::Textarea,
                                        'reg-form-preview__input--file' => $type === RegistrationFormFieldType::File,
                                    ])>{{ $placeholder }}</div>
                                @endif
                                @if (! empty($field['helper_text']))
                                    <div class="reg-form-preview__hint">{{ $field['helper_text'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($step === 0 && $workerVisible && ! $workerBefore)
                    @include('filament.registration-form-configs.preview-worker-block')
                @endif

                @if ($step === 1 && $parentVisible && ! $parentBefore)
                    @include('filament.registration-form-configs.preview-parent-block')
                @endif
            @endif
        </div>
    @endif
</div>
