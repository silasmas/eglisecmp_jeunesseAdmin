<?php

namespace App\Support;

/**
 * Préfixes S3 / disque pour organiser les fichiers par domaine métier.
 */
final class StoragePath
{
    public const PROFILES = 'profiles';

    public const RETREAT_INSCRIPTION_PHOTOS = 'retreat-inscription/photos';

    public const RETREAT_INSCRIPTION_PROOFS = 'retreat-inscription/proofs';

    public const RETREAT_DONATION_PROOFS = 'retreat-donations/proofs';

    public const CHURCH_EVENTS = 'church-events';

    public const EVENTS_AFFICHES = 'events-affiches';

    public const MEDIA_LIBRARY = 'mediatheque';

    public const IMPORTS = 'imports';

    public const EXPORTS = 'exports';

    private function __construct()
    {
    }
}
