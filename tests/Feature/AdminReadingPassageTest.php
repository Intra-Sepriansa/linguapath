<?php

use App\Models\Passage;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('admin can access reading passage cms pages but normal users cannot', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $passage = createPassage();

    $this->actingAs($user)
        ->get(route('admin.reading-passages.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.reading-passages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reading-passages/index')
            ->has('passages.data')
            ->has('filters')
            ->has('options')
            ->has('stats'));

    $this->actingAs($admin)
        ->get(route('admin.reading-passages.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reading-passages/create')
            ->has('options'));

    $this->actingAs($admin)
        ->get(route('admin.reading-passages.show', $passage))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reading-passages/show')
            ->where('passage.id', $passage->id));

    $this->actingAs($admin)
        ->get(route('admin.reading-passages.edit', $passage))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reading-passages/edit')
            ->where('passage.id', $passage->id));
});

test('admin can create a published reading passage with automatic word count', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.reading-passages.store'), [
            'title' => 'Campus Recycling Programs',
            'topic' => 'Environment',
            'passage_text' => words(320),
            'difficulty' => 'intermediate',
            'status' => 'published',
        ])
        ->assertRedirect();

    $passage = Passage::query()
        ->where('title', 'Campus Recycling Programs')
        ->firstOrFail();

    expect($passage->topic)->toBe('Environment')
        ->and($passage->word_count)->toBe(320)
        ->and($passage->status)->toBe('published')
        ->and($passage->source)->toBe('admin-cms')
        ->and($passage->reviewed_at)->not()->toBeNull();
});

test('admin can update a reading passage and recalculates word count', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $passage = createPassage([
        'title' => 'Old Title',
        'body' => words(305),
        'word_count' => 305,
        'status' => 'draft',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.reading-passages.update', $passage), [
            'title' => 'Updated Reading Passage',
            'topic' => 'Learning Science',
            'passage_text' => words(345),
            'difficulty' => 'upper_intermediate',
            'status' => 'published',
        ])
        ->assertRedirect(route('admin.reading-passages.show', $passage));

    $passage->refresh();

    expect($passage->title)->toBe('Updated Reading Passage')
        ->and($passage->topic)->toBe('Learning Science')
        ->and($passage->word_count)->toBe(345)
        ->and($passage->difficulty)->toBe('upper_intermediate')
        ->and($passage->status)->toBe('published');
});

test('admin can delete a passage only when no questions are linked', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $deletable = createPassage(['title' => 'Disposable Draft']);
    $protected = createPassage(['title' => 'Protected Passage']);

    Question::factory()->create(['passage_id' => $protected->id]);

    $this->actingAs($admin)
        ->delete(route('admin.reading-passages.destroy', $protected))
        ->assertSessionHasErrors('passage');

    expect(Passage::query()->whereKey($protected->id)->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.reading-passages.destroy', $deletable))
        ->assertRedirect(route('admin.reading-passages.index'));

    expect(Passage::query()->whereKey($deletable->id)->exists())->toBeFalse();
});

test('published passages must stay within the toefl style word count range', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.reading-passages.store'), [
            'title' => 'Too Short',
            'topic' => 'Drafting',
            'passage_text' => words(120),
            'difficulty' => 'intermediate',
            'status' => 'published',
        ])
        ->assertSessionHasErrors('passage_text');

    $this->actingAs($admin)
        ->post(route('admin.reading-passages.store'), [
            'title' => 'Draft Can Be Short',
            'topic' => 'Drafting',
            'passage_text' => words(120),
            'difficulty' => 'intermediate',
            'status' => 'draft',
        ])
        ->assertRedirect();

    $draft = Passage::query()
        ->where('title', 'Draft Can Be Short')
        ->firstOrFail();

    expect($draft->word_count)->toBe(120)
        ->and($draft->status)->toBe('draft');
});

function createPassage(array $overrides = []): Passage
{
    $body = $overrides['body'] ?? words(320);

    return Passage::query()->create(array_merge([
        'title' => 'Academic Reading Passage',
        'topic' => 'Campus life',
        'body' => $body,
        'word_count' => Passage::countWords($body),
        'difficulty' => 'intermediate',
        'source' => 'test',
        'status' => 'published',
    ], $overrides));
}

function words(int $count): string
{
    return collect(range(1, $count))
        ->map(fn (int $number): string => "academic{$number}")
        ->implode(' ');
}
