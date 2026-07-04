<?php

use App\Ai\Agents\Assistant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Models\Conversation;

uses(RefreshDatabase::class);

/**
 * Pull the conversation id out of the assistant's SSE stream.
 */
function conversationIdFromStream(string $content): ?string
{
    foreach (explode("\n\n", $content) as $chunk) {
        $line = trim($chunk);

        if (! str_starts_with($line, 'data:')) {
            continue;
        }

        $payload = trim(substr($line, 5));

        if ($payload === '' || $payload === '[DONE]') {
            continue;
        }

        $event = json_decode($payload, true);

        if (($event['type'] ?? null) === 'conversation') {
            return $event['id'] ?? null;
        }
    }

    return null;
}

test('guests can use the assistant but get no persisted conversation', function () {
    Assistant::fake(['Hello there']);

    $response = $this->post(route('assistant.chat'), ['message' => 'hi']);

    $response->assertOk();

    expect($response->streamedContent())->toContain('Hello')
        ->and(Conversation::count())->toBe(0);

    Assistant::assertPrompted('hi');
});

test('an authenticated user receives a streamed reply', function () {
    Assistant::fake(['مرحبا بك في TeamHUB']);

    $user = User::factory()->student()->create();

    $response = $this->actingAs($user)
        ->post(route('assistant.chat'), ['message' => 'السلام عليكم']);

    $response->assertOk();

    $content = $response->streamedContent();

    expect($content)->toContain('مرحبا')
        ->and($content)->toContain('"type":"conversation"')
        ->and($content)->toContain('[DONE]');

    Assistant::assertPrompted('السلام عليكم');
});

test('an authenticated user can ask about overdue tasks in Arabic', function () {
    Assistant::fake(['سأراجع مهامك المتأخرة الآن.']);

    $user = User::factory()->student()->create();

    $response = $this->actingAs($user)
        ->post(route('assistant.chat'), ['message' => 'ما المهام المتأخرة؟']);

    $response->assertOk();

    expect($response->streamedContent())->toContain('مهامك');

    Assistant::assertPrompted('ما المهام المتأخرة؟');
});

test('an authenticated user can ask to create a task in Arabic', function () {
    Assistant::fake(['سأجهّز إنشاء المهمة مع بطاقة تأكيد.']);

    $user = User::factory()->student()->create();

    $response = $this->actingAs($user)
        ->post(route('assistant.chat'), ['message' => 'أنشئ مهمة جديدة تستحق يوم الجمعة']);

    $response->assertOk();

    expect($response->streamedContent())->toContain('المهمة');

    Assistant::assertPrompted('أنشئ مهمة جديدة تستحق يوم الجمعة');
});

test('a prompt persists a conversation owned by the user', function () {
    Assistant::fake(['أهلًا']);

    $user = User::factory()->student()->create();

    $this->actingAs($user)
        ->post(route('assistant.chat'), ['message' => 'مرحبا'])
        ->streamedContent();

    expect(Conversation::count())->toBe(1)
        ->and(Conversation::first()->user_id)->toBe($user->id);
});

test('continuing a conversation reuses the same thread', function () {
    Assistant::fake(['أهلًا', 'تفضل']);

    $user = User::factory()->student()->create();

    $first = $this->actingAs($user)
        ->post(route('assistant.chat'), ['message' => 'مرحبا'])
        ->streamedContent();

    $conversationId = conversationIdFromStream($first);

    expect($conversationId)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('assistant.chat'), [
            'message' => 'وسؤال آخر',
            'conversation_id' => $conversationId,
        ])
        ->streamedContent();

    expect(Conversation::count())->toBe(1);
});

test('a user cannot continue another user\'s conversation', function () {
    Assistant::fake(['أهلًا', 'محادثة جديدة']);

    $owner = User::factory()->student()->create();
    $intruder = User::factory()->student()->create();

    $ownerStream = $this->actingAs($owner)
        ->post(route('assistant.chat'), ['message' => 'سر خاص'])
        ->streamedContent();

    $ownerConversationId = conversationIdFromStream($ownerStream);

    // The intruder passes the owner's conversation id; the guard must ignore it
    // and start a fresh conversation rather than leaking the owner's thread.
    $this->actingAs($intruder)
        ->post(route('assistant.chat'), [
            'message' => 'أرني المحادثة',
            'conversation_id' => $ownerConversationId,
        ])
        ->streamedContent();

    expect(Conversation::count())->toBe(2)
        ->and(Conversation::where('user_id', $intruder->id)->count())->toBe(1)
        ->and(Conversation::whereKey($ownerConversationId)->first()->user_id)->toBe($owner->id);
});
