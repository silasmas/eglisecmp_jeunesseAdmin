<?php

namespace Slimani\MediaManager\Models;

use App\Support\MediaUrlResolver;
use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Slimani\MediaManager\Database\Factories\FileFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Modèle fichier médiathèque (remplace le vendor) — aperçus S3 et conversions configurables.
 *
 * @property int $id
 * @property int|null $uploaded_by_user_id
 * @property int|null $folder_id
 * @property string $name
 * @property string|null $caption
 * @property string|null $alt_text
 * @property int $size
 * @property string $extension
 * @property string $mime_type
 * @property int|null $width
 * @property int|null $height
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class File extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public static ?Closure $registerMediaConversionsUsing = null;

    /**
     * @param Closure|null $callback Callback personnalisé
     * @return void
     */
    public static function registerMediaConversionsUsing(?Closure $callback): void
    {
        static::$registerMediaConversionsUsing = $callback;
    }

    /**
     * @return FileFactory
     */
    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

    protected $table = 'media_files';

    protected $fillable = [
        'uploaded_by_user_id',
        'folder_id',
        'name',
        'caption',
        'alt_text',
        'size',
        'extension',
        'mime_type',
        'width',
        'height',
    ];

    /**
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'media_taggables');
    }

    /**
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->singleFile();
    }

    /**
     * @param Media|null $media Média source
     * @return void
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        if (config('cmp.media_generate_conversions', false)) {
            $this->addMediaConversion('thumb')
                ->width(300)
                ->height(300)
                ->sharpen(10)
                ->queued();

            $this->addMediaConversion('preview')
                ->width(800)
                ->height(800)
                ->queued();
        }

        if (static::$registerMediaConversionsUsing) {
            app()->call(static::$registerMediaConversionsUsing, [
                'file' => $this,
                'media' => $media,
            ]);
        }
    }

    /**
     * @return BelongsTo
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * @return BelongsTo
     */
    public function uploader(): BelongsTo
    {
        $userModel = config('auth.providers.users.model') ?? 'App\Models\User';

        if (! class_exists($userModel)) {
            return $this->belongsTo(User::class, 'uploaded_by_user_id');
        }

        return $this->belongsTo($userModel, 'uploaded_by_user_id');
    }

    /**
     * @param string $conversion Nom de conversion ou chaîne vide
     * @param string|null $collection Collection
     * @return string|null URL d’aperçu
     */
    public function getUrl(string $conversion = '', ?string $collection = null): ?string
    {
        return MediaUrlResolver::resolve($this, $conversion, $collection);
    }
}
