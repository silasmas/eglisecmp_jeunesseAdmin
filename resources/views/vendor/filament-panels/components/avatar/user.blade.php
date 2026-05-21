@props([
    'user' => filament()->auth()->user(),
])

@php
    use App\Support\AvatarFallback;

    $src = filament()->getUserAvatarUrl($user);
    $alt = __('filament-panels::layout.avatar.alt', ['name' => filament()->getUserName($user)]);
@endphp

<x-filament::avatar
    :src="$src"
    :alt="$alt"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-avatar'])
            ->merge([
                'onerror' => AvatarFallback::imgOnErrorAttribute(),
            ])
    "
/>
