<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Tables;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RetreatActivityAttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => app(RetreatAtelierAuthorizationService::class)
                ->scopeAttendancesForUser($query->with([
                    'activityPlan.session.event',
                    'participant.atelier.responsable',
                    'participant.atelier.adjoint',
                    'participant.chambre.responsable',
                    'recorder',
                ]), Auth::user())
                ->orderBy('activity_plan_id')
                ->orderBy('participant_id')
            )
            ->header(new HtmlString(<<<'HTML'
                <style>
                    .cmp-attendance-card {
                        position: relative;
                        overflow: visible;
                        min-height: 0;
                        height: auto;
                    }

                    .cmp-attendance-card .fi-ta-record-checkbox {
                        position: absolute;
                        top: .45rem;
                        left: .45rem;
                        margin: 0 !important;
                        z-index: 20;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 1.45rem;
                        min-width: 1.45rem;
                        border-radius: .5rem;
                        background: #ffffff;
                        border: 1px solid #7b1d3e;
                        box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
                        cursor: pointer !important;
                        pointer-events: auto !important;
                        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
                    }

                    .cmp-attendance-card .fi-ta-record-checkbox input[type="checkbox"] {
                        accent-color: #7b1d3e;
                        width: 1rem;
                        height: 1rem;
                        border: 2px solid #7b1d3e;
                        background: #ffffff;
                        cursor: pointer;
                    }

                    .cmp-attendance-card .fi-ta-record-checkbox:has(input[type="checkbox"]:checked) {
                        background: #7b1d3e;
                        border-color: #7b1d3e;
                        box-shadow: 0 0 0 3px rgba(123, 29, 62, .18);
                    }

                    .cmp-attendance-card .fi-ta-record-checkbox input[type="checkbox"]:checked {
                        background-color: #ffffff !important;
                        border-color: #ffffff !important;
                    }

                    .cmp-attendance-card .fi-ta-record-checkbox:hover {
                        transform: scale(1.08);
                        box-shadow: 0 0 0 3px rgba(123, 29, 62, .12);
                    }

                    .cmp-attendance-card .fi-ta-record-content-ctn,
                    .cmp-attendance-card .fi-ta-record-content-ctn > div,
                    .cmp-attendance-card .fi-ta-record-content {
                        width: 100%;
                        min-height: 0;
                        height: auto;
                    }

                    .cmp-attendance-card .fi-ta-record-content-ctn {
                        display: block;
                        padding: 0;
                        gap: 0;
                    }

                    .cmp-attendance-card .fi-ta-record-content-ctn > :first-child {
                        flex: none;
                    }

                    .cmp-attendance-card .fi-ta-record-content {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .cmp-attendance-card .fi-ta-actions {
                        position: absolute;
                        top: .45rem;
                        right: .15rem;
                        z-index: 20;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 1.35rem;
                        min-width: 1.35rem;
                        border-radius: .45rem;
                        background: transparent;
                        box-shadow: none;
                        pointer-events: auto;
                    }

                    .cmp-attendance-card .fi-ta-actions .fi-icon-btn {
                        margin-left: auto;
                    }

                    .cmp-attendance-card .fi-ta-record-content-ctn {
                        position: relative;
                        z-index: 10;
                    }

                    .fi-modal-window.cmp-attendance-modal {
                        width: fit-content;
                        max-width: calc(100vw - 2rem);
                    }

                    .cmp-attendance-modal .fi-modal-content,
                    .cmp-attendance-modal .fi-in {
                        display: flex;
                        justify-content: center;
                    }

                    .cmp-attendance-modal .fi-section {
                        width: fit-content;
                        min-width: min(28rem, calc(100vw - 4rem));
                        max-width: calc(100vw - 4rem);
                    }
                </style>

                <script>
                    window.cmpAttendanceFloating = window.cmpAttendanceFloating || {
                        detail: null,
                        image: null,
                    };

                    window.cmpEnsureAttendanceDetailPopover = function () {
                        if (window.cmpAttendanceFloating.detail) {
                            return window.cmpAttendanceFloating.detail;
                        }

                        const popover = document.createElement('div');
                        popover.style.display = 'none';
                        popover.style.position = 'fixed';
                        popover.style.zIndex = '99999';
                        popover.style.width = 'max-content';
                        popover.style.maxWidth = 'min(42rem, calc(100vw - 2rem))';
                        popover.style.border = '1px solid #e5e7eb';
                        popover.style.borderRadius = '.75rem';
                        popover.style.background = '#ffffff';
                        popover.style.padding = '.75rem';
                        popover.style.textAlign = 'left';
                        popover.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1)';
                        document.body.appendChild(popover);

                        window.cmpAttendanceFloating.detail = popover;

                        return popover;
                    };

                    window.cmpShowAttendancePopover = function (trigger) {
                        const card = trigger.closest('[data-attendance-card-content]');
                        const template = card ? card.querySelector('[data-attendance-popover-template]') : null;
                        const popover = window.cmpEnsureAttendanceDetailPopover();

                        if (! template || ! popover) {
                            return;
                        }

                        const hostRect = trigger.getBoundingClientRect();

                        popover.innerHTML = template.innerHTML;
                        popover.style.display = 'block';
                        popover.style.position = 'fixed';
                        popover.style.top = (hostRect.bottom + 8) + 'px';
                        popover.style.left = (hostRect.left + (hostRect.width / 2)) + 'px';
                        popover.style.right = 'auto';
                        popover.style.bottom = 'auto';
                        popover.style.transform = 'translateX(-50%)';

                        requestAnimationFrame(function () {
                            let rect = popover.getBoundingClientRect();

                            if ((rect.bottom > (window.innerHeight - 12)) && (hostRect.top > (rect.height + 16))) {
                                popover.style.top = (hostRect.top - rect.height - 8) + 'px';
                            }

                            rect = popover.getBoundingClientRect();

                            if (rect.right > (window.innerWidth - 12)) {
                                popover.style.left = (window.innerWidth - rect.width - 12) + 'px';
                                popover.style.transform = 'none';
                            }

                            rect = popover.getBoundingClientRect();

                            if (rect.left < 12) {
                                popover.style.left = '12px';
                                popover.style.transform = 'none';
                            }
                        });
                    };

                    window.cmpHideAttendancePopover = function () {
                        const popover = window.cmpAttendanceFloating.detail;

                        if (popover) {
                            popover.style.display = 'none';
                        }
                    };

                    window.cmpEnsureAttendanceImagePreview = function () {
                        if (window.cmpAttendanceFloating.image) {
                            return window.cmpAttendanceFloating.image;
                        }

                        const preview = document.createElement('div');
                        preview.style.display = 'none';
                        preview.style.position = 'fixed';
                        preview.style.zIndex = '99999';
                        preview.style.border = '1px solid #e5e7eb';
                        preview.style.borderRadius = '1rem';
                        preview.style.background = '#ffffff';
                        preview.style.padding = '.5rem';
                        preview.style.pointerEvents = 'none';
                        preview.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, .16), 0 8px 10px -6px rgba(0, 0, 0, .12)';
                        preview.innerHTML = '<img alt="" style="display: block; width: 220px; height: 220px; border-radius: .75rem; object-fit: cover;">';
                        document.body.appendChild(preview);

                        window.cmpAttendanceFloating.image = preview;

                        return preview;
                    };

                    window.cmpMoveAttendanceImagePreview = function (event) {
                        const preview = window.cmpAttendanceFloating.image;

                        if (! preview || preview.style.display === 'none') {
                            return;
                        }

                        const offset = 16;
                        const boundary = 260;
                        let left = event.clientX + offset;
                        let top = event.clientY + offset;

                        if (left + boundary > window.innerWidth) {
                            left = event.clientX - boundary - offset;
                        }

                        if (top + boundary > window.innerHeight) {
                            top = event.clientY - boundary - offset;
                        }

                        preview.style.left = Math.max(left, 12) + 'px';
                        preview.style.top = Math.max(top, 12) + 'px';
                    };

                    window.cmpShowAttendanceImagePreview = function (trigger, event) {
                        const url = trigger.getAttribute('data-attendance-photo');
                        const alt = trigger.getAttribute('data-attendance-photo-alt') || '';
                        const preview = window.cmpEnsureAttendanceImagePreview();
                        const image = preview.querySelector('img');

                        if (! url || ! image) {
                            return;
                        }

                        image.src = url;
                        image.alt = alt;
                        preview.style.display = 'block';

                        if (event) {
                            window.cmpMoveAttendanceImagePreview(event);

                            return;
                        }

                        const rect = trigger.getBoundingClientRect();
                        preview.style.left = Math.max(rect.right + 12, 12) + 'px';
                        preview.style.top = Math.max(rect.top, 12) + 'px';
                    };

                    window.cmpHideAttendanceImagePreview = function () {
                        const preview = window.cmpAttendanceFloating.image;

                        if (preview) {
                            preview.style.display = 'none';
                        }
                    };
                </script>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .75rem; margin: .75rem 1rem 1rem; font-size: .82rem;">
                    <span style="font-weight: 700; color: #111827;">Legende :</span>
                    <span style="display: inline-flex; align-items: center; gap: .35rem;"><span style="width: .75rem; height: .75rem; border-radius: 9999px; background: #22c55e;"></span>Present</span>
                    <span style="display: inline-flex; align-items: center; gap: .35rem;"><span style="width: .75rem; height: .75rem; border-radius: 9999px; background: #ef4444;"></span>Absent</span>
                    <span style="display: inline-flex; align-items: center; gap: .35rem;"><span style="width: .75rem; height: .75rem; border-radius: 9999px; background: #3b82f6;"></span>Excuse</span>
                    <span style="display: inline-flex; align-items: center; gap: .35rem;"><span style="width: .75rem; height: .75rem; border-radius: 9999px; background: #f59e0b;"></span>En retard</span>
                </div>
            HTML))
            ->contentGrid([
                'sm' => 2,
                'md' => 4,
                'xl' => 6,
                '2xl' => 8,
            ])
            ->recordClasses('cmp-attendance-card')
            ->groups([
                Group::make('activityPlan.title')
                    ->label('Activité')
                    ->collapsible(),
                Group::make('participant.atelier.numero')
                    ->label('Atelier')
                    ->getTitleFromRecordUsing(fn (RetreatActivityAttendance $record): string => filled($record->participant?->atelier?->numero)
                        ? 'Atelier '.$record->participant->atelier->numero
                            .' · Resp. '.($record->participant->atelier->responsable?->name ?? '—')
                        : 'Sans atelier')
                    ->collapsible(),
                Group::make('activityPlan.session.start_at')
                    ->label('Jour')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup('activityPlan.title')
            ->columns([
                View::make('filament.tables.columns.retreat-activity-attendance-card')
                    ->components([
                        TextColumn::make('participant.nom')->searchable(),
                        TextColumn::make('participant.prenom')->searchable(),
                        TextColumn::make('participant.email')->searchable(),
                        TextColumn::make('participant.telephone')->searchable(),
                        TextColumn::make('participant.chambre.nom')->searchable(),
                        TextColumn::make('participant.chambre.responsable.name')->searchable(),
                        TextColumn::make('participant.atelier.numero')->searchable(),
                        TextColumn::make('participant.atelier.responsable.name')->searchable(),
                        TextColumn::make('activityPlan.title')->searchable(),
                        TextColumn::make('activityPlan.session.event.name')->searchable(),
                        TextColumn::make('recorder.name')->searchable(),
                        TextColumn::make('status')->searchable(),
                        TextColumn::make('scan_source')->searchable(),
                        TextColumn::make('note')->searchable(),
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions(
                ActionGroup::make([
                    ViewAction::make()
                        ->modal()
                        ->modalWidth(Width::FitContent)
                        ->modalAlignment(Alignment::Center)
                        ->extraModalWindowAttributes(['class' => 'cmp-attendance-modal']),
                    EditAction::make()
                        ->modal()
                        ->modalWidth(Width::FitContent)
                        ->modalAlignment(Alignment::Center)
                        ->extraModalWindowAttributes(['class' => 'cmp-attendance-modal'])
                        ->visible(fn (RetreatActivityAttendance $record): bool => app(RetreatAtelierAuthorizationService::class)
                            ->canManageParticipant(Auth::user(), $record->participant)),
                    Action::make('mark_present')
                        ->label('Présent')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (RetreatActivityAttendance $record): bool => app(RetreatAtelierAuthorizationService::class)
                            ->canManageParticipant(Auth::user(), $record->participant))
                        ->action(function (RetreatActivityAttendance $record): void {
                            $record->update([
                                'status' => 'present',
                                'check_in_at' => now(),
                                'recorded_by' => Auth::id(),
                                'scan_source' => 'manual',
                            ]);
                        }),
                    DeleteAction::make()
                        ->visible(fn (RetreatActivityAttendance $record): bool => app(RetreatAtelierAuthorizationService::class)
                            ->canManageParticipant(Auth::user(), $record->participant)),
                    Action::make('open_in_new_tab')
                        ->label('Ouvrir dans un onglet')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (RetreatActivityAttendance $record): string => RetreatActivityAttendanceResource::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                ])
                    ->iconButton()
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions'),
                RecordActionsPosition::AfterContent,
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
