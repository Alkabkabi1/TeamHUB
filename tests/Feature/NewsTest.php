<?php

use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| news.create — GET clubs/{club}/news/create
|--------------------------------------------------------------------------
*/

test('supervisor can view the news create form', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->get(route('news.create', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsForm')
            ->where('club.id', $club->id)
            ->where('club.name', $club->name)
        );
});

test('university staff can view the news create form for any club', function () {
    $staff = User::factory()->universityStaff()->create();
    $club = Club::factory()->create();

    $this->actingAs($staff)
        ->get(route('news.create', $club))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('NewsForm'));
});

test('non-supervisor student gets 403 on news create', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $this->actingAs($student)
        ->get(route('news.create', $club))
        ->assertForbidden();
});

test('guest is redirected from news create', function () {
    $club = Club::factory()->create();

    $this->get(route('news.create', $club))
        ->assertRedirect(route('login'));
});

/*
|--------------------------------------------------------------------------
| news.store — POST clubs/{club}/news
|--------------------------------------------------------------------------
*/

test('supervisor can store a news post', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), [
            'title' => 'Test News Title',
            'body' => 'This is the body of the news post.',
        ])
        ->assertRedirect(route('clubs.manage', $club));

    expect(Post::where('club_id', $club->id)->where('title', 'Test News Title')->exists())->toBeTrue();
});

test('non-supervisor student gets 403 on news store', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $this->actingAs($student)
        ->post(route('news.store', $club), [
            'title' => 'Unauthorized Post',
            'body' => 'Should not be created.',
        ])
        ->assertForbidden();
});

test('image larger than 10MB is rejected on news store', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    // Create a fake file larger than 10MB (10241 KB = 10.001 MB)
    $largeImage = UploadedFile::fake()->image('large.jpg')->size(10241);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), [
            'title' => 'Post with large image',
            'body' => 'Body text.',
            'images' => [$largeImage],
        ])
        ->assertSessionHasErrors('images.0');
});

test('approved members are notified when a post is stored', function () {
    Notification::fake();
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $member1 = User::factory()->student()->create();
    $member2 = User::factory()->student()->create();

    ClubMembership::factory()->member()->approved()->create([
        'user_id' => $member1->id,
        'club_id' => $club->id,
    ]);

    ClubMembership::factory()->member()->approved()->create([
        'user_id' => $member2->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), [
            'title' => 'Notification Test Post',
            'body' => 'Body of the notification post.',
        ]);

    Notification::assertSentTo([$member1, $member2], NewPostNotification::class);
});

test('supervisor not notified when a post is stored', function () {
    Notification::fake();

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), [
            'title' => 'No Notify Supervisor',
            'body' => 'Body.',
        ]);

    Notification::assertNotSentTo($supervisor, NewPostNotification::class);
});

test('multiple images are stored in the media library when uploading a post', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $this->actingAs($supervisor)
        ->post(route('news.store', $club), [
            'title' => 'Post with images',
            'body' => 'Body with images.',
            'images' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ]);

    $post = Post::where('club_id', $club->id)->first();
    expect($post->getMedia(Post::IMAGE_COLLECTION))->toHaveCount(2);
    expect($post->coverImageUrl())->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| news.destroy — DELETE news/{post}
|--------------------------------------------------------------------------
*/

test('supervisor can delete a post belonging to their club', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($supervisor)
        ->delete(route('news.destroy', $post))
        ->assertRedirect();

    expect(Post::find($post->id))->toBeNull();
});

test('deleting a post removes its media from the library', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->withImages(2)->create(['club_id' => $club->id]);
    $media = $post->getMedia(Post::IMAGE_COLLECTION)->first();

    expect($media)->not->toBeNull();
    Storage::disk('public')->assertExists("{$media->id}/{$media->file_name}");

    $this->actingAs($supervisor)
        ->delete(route('news.destroy', $post));

    expect(Post::find($post->id))->toBeNull();
    Storage::disk('public')->assertMissing("{$media->id}/{$media->file_name}");
});

test('non-supervisor cannot delete a post', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();

    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($student)
        ->delete(route('news.destroy', $post))
        ->assertForbidden();

    $this->assertModelExists($post);
});

/*
|--------------------------------------------------------------------------
| news.index — GET news (public feed)
|--------------------------------------------------------------------------
*/

test('the public news feed lists published posts', function () {
    $published = Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsPage')
            ->has('posts', 1)
            ->where('posts.0.id', $published->id)
        );
});

test('the public news feed excludes future-dated posts', function () {
    Post::factory()->create(['published_at' => now()->addWeek()]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsPage')
            ->has('posts', 0)
        );
});

test('the public news feed can be searched by title', function () {
    Post::factory()->create(['title' => 'Robotics workshop announced', 'published_at' => now()->subDay()]);
    Post::factory()->create(['title' => 'Poetry evening recap', 'published_at' => now()->subDay()]);

    $this->get(route('news.index', ['search' => 'Robotics']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsPage')
            ->has('posts', 1)
            ->where('posts.0.title', 'Robotics workshop announced')
        );
});

/*
|--------------------------------------------------------------------------
| news.show — GET news/{post} (public article)
|--------------------------------------------------------------------------
*/

test('a published post can be viewed publicly', function () {
    $post = Post::factory()->create(['published_at' => now()->subDay()]);

    $this->get(route('news.show', $post))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsArticle')
            ->where('post.id', $post->id)
            ->where('post.title', $post->title)
        );
});

test('a future-dated post returns 404 on the public article page', function () {
    $post = Post::factory()->create(['published_at' => now()->addWeek()]);

    $this->get(route('news.show', $post))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| news.edit — GET clubs/{club}/news/{post}/edit
|--------------------------------------------------------------------------
*/

test('supervisor can view the news edit form', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($supervisor)
        ->get(route('news.edit', ['club' => $club, 'post' => $post]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('NewsForm')
            ->where('mode', 'edit')
            ->where('post.id', $post->id)
        );
});

test('non-supervisor student gets 403 on news edit', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();
    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($student)
        ->get(route('news.edit', ['club' => $club, 'post' => $post]))
        ->assertForbidden();
});

test('editing a post belonging to another club returns 404', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create(['club_id' => $otherClub->id]);

    $this->actingAs($supervisor)
        ->get(route('news.edit', ['club' => $club, 'post' => $post]))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| news.update — PUT clubs/{club}/news/{post}
|--------------------------------------------------------------------------
*/

test('supervisor can update a post', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create([
        'club_id' => $club->id,
        'title' => 'Original title',
        'body' => 'Original body',
    ]);

    $this->actingAs($supervisor)
        ->put(route('news.update', ['club' => $club, 'post' => $post]), [
            'title' => 'Updated title',
            'body' => 'Updated body',
        ])
        ->assertRedirect(route('clubs.manage', $club));

    expect($post->fresh()->title)->toBe('Updated title')
        ->and($post->fresh()->body)->toBe('Updated body');
});

test('updating a post can remove an existing image and add new ones', function () {
    Storage::fake('public');

    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->withImages(1)->create(['club_id' => $club->id]);
    $oldMedia = $post->getMedia(Post::IMAGE_COLLECTION)->first();

    $this->actingAs($supervisor)
        ->put(route('news.update', ['club' => $club, 'post' => $post]), [
            'title' => 'With new image',
            'body' => 'Body.',
            'images' => [UploadedFile::fake()->image('new.jpg')],
            'removed_media' => [$oldMedia->id],
        ]);

    $freshMedia = $post->fresh()->getMedia(Post::IMAGE_COLLECTION);
    expect($freshMedia)->toHaveCount(1);
    expect($freshMedia->first()->id)->not->toBe($oldMedia->id);
    Storage::disk('public')->assertMissing("{$oldMedia->id}/{$oldMedia->file_name}");
});

test('updating a post requires a title', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($supervisor)
        ->put(route('news.update', ['club' => $club, 'post' => $post]), [
            'title' => '',
            'body' => 'Body without a title.',
        ])
        ->assertSessionHasErrors('title');
});

test('non-supervisor student gets 403 on news update', function () {
    $student = User::factory()->student()->create();
    $club = Club::factory()->create();
    $post = Post::factory()->create(['club_id' => $club->id]);

    $this->actingAs($student)
        ->put(route('news.update', ['club' => $club, 'post' => $post]), [
            'title' => 'Hacked title',
            'body' => 'Body.',
        ])
        ->assertForbidden();
});

test('updating a post belonging to another club returns 404', function () {
    $supervisor = User::factory()->clubSupervisor()->create();
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();

    ClubMembership::factory()->supervisor()->approved()->create([
        'user_id' => $supervisor->id,
        'club_id' => $club->id,
    ]);

    $post = Post::factory()->create(['club_id' => $otherClub->id]);

    $this->actingAs($supervisor)
        ->put(route('news.update', ['club' => $club, 'post' => $post]), [
            'title' => 'Updated title',
            'body' => 'Updated body',
        ])
        ->assertNotFound();
});
