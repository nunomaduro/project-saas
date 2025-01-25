<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Jobs\IngestActivity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach()->only();

it('can create an activity', function () {
    // Arrange...
    Queue::fake([IngestActivity::class]);
    $authUser = User::factory()->create();
    $project = Project::factory()->for($authUser)->create()->fresh();

    $events = [
        EventType::view('/about'),
    ];

    // Act...
    $response = $this->actingAs($authUser)
        ->postJson(route('api.activities.store', $project), [
            'events' => $events,
        ]);

    // Assert...
    $response->assertStatus(201);

    $activities = $project->activities;
    expect($activities)->toHaveCount(1);

    Queue::assertPushed(IngestActivity::class, 1);
});

it('cannot create an activity for a project that does not belong to the user', function () {
    Queue::fake([IngestActivity::class]);
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.activities.store', $project), [
            'events' => [
                EventType::view('/about'),
            ],
        ])
        ->assertStatus(403);

    $this->assertDatabaseCount('activities', 0);

    Queue::assertNotPushed(IngestActivity::class);
});

it('does not handle empty events', function () {
    // Arrange...
    Queue::fake([IngestActivity::class]);
    $authUser = User::factory()->create();
    $project = Project::factory()->for($authUser)->create()->fresh();

    // Act...
    $response = $this->actingAs($authUser)
        ->postJson(route('api.activities.store', $project), [
            'events' => [],
        ]);

    // Assert...
    $response->assertStatus(422)->assertJsonValidationErrors([
        'events' => 'The events field is required.',
    ]);

    $activities = $project->activities;
    expect($activities)->toHaveCount(0);

    Queue::assertNotPushed(IngestActivity::class);
});

it('does not handle corrupted events', function () {
    // Arrange...
    Queue::fake([IngestActivity::class]);
    $authUser = User::factory()->create();
    $project = Project::factory()->for($authUser)->create()->fresh();

    // Act...
    $response = $this->actingAs($authUser)
        ->postJson(route('api.activities.store', $project), [
            'events' => [
                1,
                'string',
                [
                    1,
                ],
                [
                    'type' => 'view',
                ],
                [
                    'type' => 'view',
                    'payload' => [
                        //
                    ],
                ],
            ],
        ]);

    // Assert...
    $response->assertStatus(422)->assertJsonValidationErrors([
        'events' => 'The events field is invalid.',
    ]);

    $activities = $project->activities;
    expect($activities)->toHaveCount(0);

    Queue::assertNotPushed(IngestActivity::class);
});
