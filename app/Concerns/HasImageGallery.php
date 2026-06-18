<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared image-gallery behaviour for models that expose a multi-image
 * collection through spatie/laravel-medialibrary. Used by Events and News
 * (Post) so both features handle uploads and display identically.
 */
trait HasImageGallery
{
    /**
     * The media collection that holds the model's gallery images.
     */
    public const string IMAGE_COLLECTION = 'images';

    /**
     * Public URLs for every image, in display order. Render these on cards
     * and galleries. Eager-load the `media` relation to avoid N+1 queries.
     *
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return $this->getMedia(self::IMAGE_COLLECTION)
            ->map(fn (Media $media): string => $media->getUrl())
            ->all();
    }

    /**
     * The first image URL, or null when the gallery is empty. Convenient for
     * card thumbnails and cover images.
     */
    public function coverImageUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
    }

    /**
     * Virtual `image_url` attribute (the cover image). Not appended by default
     * to avoid N+1 — call `->append('image_url')` on a collection that has the
     * `media` relation eager-loaded.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->coverImageUrl());
    }

    /**
     * Gallery entries including media ids, for edit forms that need to render
     * existing images and let the user remove them by id.
     *
     * @return list<array{id: int, url: string, name: string}>
     */
    public function imageGallery(): array
    {
        return $this->getMedia(self::IMAGE_COLLECTION)
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'name' => $media->name,
            ])
            ->all();
    }
}
