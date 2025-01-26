<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Jobs\IngestActivity;
use App\Models\Project;
use Illuminate\Support\Facades\Queue;

it('can create an activity', function () {
    // Arrange...
    Queue::fake([IngestActivity::class]);
    $project = Project::factory()->create()->fresh();

    $events = [
        EventType::view('/about'),
    ];

    // Act...
    $response = $this->postJson(route('api.activities.store', $project), [
        'events' => $events,
    ]);

    // Assert...
    $response->assertStatus(201);

    $activities = $project->activities;
    expect($activities)->toHaveCount(1);

    Queue::assertPushed(IngestActivity::class, 1);
});

it('does not handle empty events', function () {
    // Arrange...
    Queue::fake([IngestActivity::class]);
    $project = Project::factory()->create()->fresh();

    // Act...
    $response = $this->postJson(route('api.activities.store', $project), [
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
    $project = Project::factory()->create()->fresh();

    // Act...
    $response = $this->postJson(route('api.activities.store', $project), [
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
