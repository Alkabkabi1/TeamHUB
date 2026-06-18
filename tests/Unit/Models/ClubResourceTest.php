<?php

use App\Models\Club;
use App\Models\ClubResource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('club resource type constants are defined', function () {
    expect(ClubResource::TYPE_DOWNLOAD)->toBe('download')
        ->and(ClubResource::TYPE_MEDIA)->toBe('media');
});

test('club resource belongs to club', function () {
    $resource = ClubResource::factory()->create();

    expect($resource->club())->toBeInstanceOf(BelongsTo::class)
        ->and($resource->club->id)->toBe($resource->club_id);
});

test('downloads scope returns only download resources', function () {
    $club = Club::factory()->create();

    ClubResource::factory()->download()->count(2)->create(['club_id' => $club->id]);
    ClubResource::factory()->media()->create(['club_id' => $club->id]);

    $downloads = ClubResource::query()->downloads()->where('club_id', $club->id)->get();

    expect($downloads)->toHaveCount(2)
        ->and($downloads->every(fn (ClubResource $resource): bool => $resource->type === ClubResource::TYPE_DOWNLOAD))->toBeTrue();
});

test('media scope returns only media resources', function () {
    $club = Club::factory()->create();

    ClubResource::factory()->media()->count(2)->create(['club_id' => $club->id]);
    ClubResource::factory()->download()->create(['club_id' => $club->id]);

    $media = ClubResource::query()->media()->where('club_id', $club->id)->get();

    expect($media)->toHaveCount(2)
        ->and($media->every(fn (ClubResource $resource): bool => $resource->type === ClubResource::TYPE_MEDIA))->toBeTrue();
});

test('club resource casts published_at to datetime', function () {
    $publishedAt = now()->startOfSecond();

    $resource = ClubResource::factory()->create([
        'published_at' => $publishedAt,
    ]);

    expect($resource->published_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($resource->published_at->equalTo($publishedAt))->toBeTrue();
});

test('download and media factories set expected types', function () {
    $download = ClubResource::factory()->download()->make();
    $media = ClubResource::factory()->media()->make();

    expect($download->type)->toBe(ClubResource::TYPE_DOWNLOAD)
        ->and($media->type)->toBe(ClubResource::TYPE_MEDIA);
});
