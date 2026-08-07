<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-identification"
                        class="h-5 w-5 text-primary-600 dark:text-primary-400"
                    />
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Studio badges
                    </h3>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Générez et imprimez les badges de la session courante
                    @if ($eventName)
                        — <span class="font-medium text-gray-700 dark:text-gray-200">{{ $eventName }}</span>
                    @else
                        — aucune retraite opérationnelle détectée
                    @endif
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    Connecté : {{ $userName }}
                    · {{ $participantsCount }} participant(s) actif(s)
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-filament::button
                    tag="a"
                    href="{{ $studioUrl }}"
                    icon="heroicon-o-arrow-top-right-on-square"
                    color="primary"
                >
                    Ouvrir le studio
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
