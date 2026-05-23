@php
    use Slimani\MediaManager\Models\Folder;
    use Illuminate\Support\Number;

    /** @var \Slimani\MediaManager\Models\File|\Slimani\MediaManager\Models\Folder $item */
    $item = $item ?? $get('item');
    $isSelected = collect($this->selectedItems ?? [])->contains($item instanceof Folder ? "folder-{$item->id}" : "file-{$item->id}");
    $isFolder = $item instanceof Folder;
    $imagePreviewUrl = $isFolder ? null : media_preview_url($item);
@endphp

<div
    {{ $attributes->class([
        'fi-media-item group',
        'fi-is-selected' => $isSelected,
        'fi-is-disabled' => ! ($isAccepted ?? true),
    ]) }}
    @if($isFolder)
        x-on:dblclick.stop="$wire.setCurrentFolder({{ $item->id }})"
    @endif
    x-data="{
        longPressTimeout: null,
        isLongPress: false,
        isDragging: false,
        startPress(e) {
            if (!{{ $isAccepted ? 'true' : 'false' }}) return;
            this.isDragging = false;
            this.isLongPress = false;
            this.longPressTimeout = setTimeout(() => {
                this.isLongPress = true;
                $wire.toggleSelection('{{ $isFolder ? "folder-" : "file-" }}{{ $item->id }}');
                if ('vibrate' in navigator) navigator.vibrate(50);
            }, 500);
        },
        cancelPress() {
            clearTimeout(this.longPressTimeout);
        },
        handleSingleClick() {
            if (this.isLongPress) return;

            @if($isFolder)
                $wire.toggleSelection('folder-{{ $item->id }}');
            @else
                if (!{{ $isAccepted ? 'true' : 'false' }}) return;

                $wire.selectFile({{ $item->id }});
                if (! $wire.isPicker) {
                    $wire.showDetails = true;
                }
            @endif
        },
        openFolderOnDoubleClick() {
            @if($isFolder)
                $wire.setCurrentFolder({{ $item->id }});
            @endif
        }
    }"
    x-on:mousedown="startPress"
    x-on:touchstart.passive="startPress"
    x-on:mouseup="cancelPress"
    x-on:mouseleave="cancelPress"
    x-on:touchend="cancelPress"
    x-on:touchmove.passive="isDragging = true; cancelPress()"
    x-on:contextmenu.prevent=""
    x-on:click.capture="if (isLongPress) { $event.stopPropagation(); $event.preventDefault(); isLongPress = false; }"
>
    <div class="fi-media-item-thumbnail-container">
        @if($isFolder)
            <button
                type="button"
                class="fi-media-open-folder-button"
                wire:click.stop="setCurrentFolder({{ $item->id }})"
                title="Ouvrir le dossier"
                aria-label="Ouvrir le dossier {{ $item->name }}"
            >
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-3.5 h-3.5" />
            </button>
            <div class="relative transition-transform duration-300 group-hover:scale-110">
                <x-filament::icon icon="heroicon-s-folder" class="fi-media-item-folder-icon" />
                <div class="absolute inset-x-0 bottom-2 flex justify-center">
                    <span class="px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px] font-bold">
                        {{ $item->children_count + $item->files_count }}
                    </span>
                </div>
            </div>
        @else
            @if(str($item->mime_type)->startsWith('image/') || str($item->mime_type)->startsWith('video/'))
                @if($imagePreviewUrl)
                    <img
                        src="{{ $imagePreviewUrl }}"
                        alt="{{ $item->name }}"
                        class="fi-media-item-file-image"
                        loading="lazy"
                    >
                @else
                    <div class="flex h-full w-full items-center justify-center bg-gray-100 dark:bg-gray-800">
                        <x-filament::icon icon="heroicon-s-photo" class="h-10 w-10 text-gray-400" />
                    </div>
                @endif

                @if(str($item->mime_type)->startsWith('video/'))
                    <x-filament::icon icon="heroicon-s-play-circle" class="fi-media-item-video-icon" />
                @endif
            @else
                <div class="flex flex-col items-center gap-2 transition-transform duration-300 group-hover:scale-110">
                    <div class="relative">
                        <x-filament::icon icon="heroicon-s-document-text" class="fi-media-item-file-icon" />
                        <span class="absolute bottom-1 right-0 px-1 py-0.5 rounded fi-media-extension-badge ext-{{ strtolower($item->extension) }} text-[8px] font-bold uppercase ring-1 ring-white dark:ring-gray-800">
                            {{ $item->extension ?? __('media-manager::media-manager.common.file_fallback') }}
                        </span>
                    </div>
                </div>
            @endif
        @endif

        <div class="fi-media-item-selection-badge group-selection {{ $isSelected ? 'scale-100 opacity-100' : 'scale-75 opacity-0 group-hover:opacity-100 group-hover:scale-100' }}">
            <button
                type="button"
                @if(! $isAccepted) disabled @endif
                x-on:click.stop="$wire.toggleSelection('{{ $isFolder ? "folder-" : "file-" }}{{ $item->id }}')"
                class="fi-media-item-selection-button"
            >
                <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
            </button>
        </div>

        <div
            class="absolute inset-0 z-10 cursor-pointer"
            x-on:click.stop="handleSingleClick"
            x-on:dblclick.stop="openFolderOnDoubleClick()"
            title="@if($isFolder) Double-cliquer pour ouvrir @endif"
        ></div>
    </div>

    <div
        class="fi-media-item-info"
        @if($isFolder)
            x-on:dblclick.stop="$wire.setCurrentFolder({{ $item->id }})"
        @endif
    >
        <div class="min-w-0">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate flex items-center gap-1.5" title="{{ $item->name }}">
                @if($isFolder)
                    <x-filament::icon icon="heroicon-m-folder" class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                @else
                    <x-filament::icon icon="heroicon-m-document" class="w-3.5 h-3.5 text-gray-400 shrink-0" />
                @endif
                <span class="truncate">{{ $item->name }}</span>
            </h4>

            <div class="mt-1 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                @if($isFolder)
                    <span>
                        {{ trans_choice('media-manager::media-manager.common.item_count', $item->children_count + $item->files_count, ['count' => $item->children_count + $item->files_count]) }}
                    </span>
                    <button
                        type="button"
                        class="text-primary-600 hover:text-primary-500 font-semibold"
                        wire:click.stop="setCurrentFolder({{ $item->id }})"
                    >
                        Ouvrir
                    </button>
                @else
                    <span>{{ Number::fileSize($item->size ?? 0) }}</span>
                    <span class="uppercase tracking-wider opacity-60">{{ $item->extension }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
