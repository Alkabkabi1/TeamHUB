<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

/**
 * Shared controller helper that syncs an uploaded image gallery onto any
 * media-library model. Removes media selected for deletion, then appends any
 * newly uploaded files. Used by both the Events and News controllers.
 */
trait SyncsImageUploads
{
    /**
     * @param  list<UploadedFile>|UploadedFile  $files
     * @param  list<int|string>  $removedMediaIds
     */
    protected function syncImageGallery(
        HasMedia $model,
        string $collection,
        array|UploadedFile $files = [],
        array $removedMediaIds = [],
    ): void {
        foreach ($removedMediaIds as $mediaId) {
            $model->getMedia($collection)
                ->firstWhere('id', (int) $mediaId)
                ?->delete();
        }

        foreach (is_array($files) ? $files : [$files] as $file) {
            $model->addMedia($file)->toMediaCollection($collection);
        }
    }
}
