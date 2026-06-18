<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Move any existing single post image (image_path) into the shared
     * media-library "images" collection, then drop the legacy column.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('posts', 'image_path')) {
            return;
        }

        Post::query()
            ->whereNotNull('image_path')
            ->each(function (Post $post): void {
                $path = $post->getRawOriginal('image_path');

                if ($path && Storage::disk('public')->exists($path)) {
                    $post->addMediaFromDisk($path, 'public')
                        ->preservingOriginal()
                        ->toMediaCollection(Post::IMAGE_COLLECTION);
                }
            });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
        });
    }
};
