<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('returns a monthly report for the current month', function () {
    $month = now()->format('Y-m');

    Transaction::factory()->income()->confirmed()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '1000.0000',
        'projected_date' => now()->startOfMonth()->toDateString(),
    ]);

    Transaction::factory()->expense()->confirmed()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '400.0000',
        'projected_date' => now()->startOfMonth()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/reports/monthly?month={$month}"
    );

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(1);
    expect($data[0]['month'])->toBe($month);
    expect((float) $data[0]['confirmed_income'])->toBe(1000.0);
    expect((float) $data[0]['confirmed_expenses'])->toBe(400.0);
});

it('returns a multi-month projection', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/reports/projection?months=3"
    );

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'operational_balance',
                'projected_balance',
                'months',
                'periods',
            ],
        ]);

    expect($response->json('data.months'))->toBe(3);
    expect($response->json('data.periods'))->toHaveCount(3);
});

it('indicates a deficit month in projection', function () {
    // Large projected expense in the current month
    Transaction::factory()->expense()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '99999.0000',
        'projected_date' => now()->startOfMonth()->toDateString(),
        'confirmed_at' => null,
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/reports/projection?months=1"
    );

    $response->assertSuccessful();

    expect($response->json('data.periods.0.deficit'))->toBeTrue();
});

it('exports transactions as CSV', function () {
    Transaction::factory()->count(3)->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get(
        "/api/v1/workspaces/{$this->workspace->id}/reports/export/csv"
    );

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $lines = explode("\n", trim($response->getContent()));
    // Header row + 3 transaction rows
    expect(count($lines))->toBe(4);
});

it('exports transactions as CSV filtered by month', function () {
    $thisMonth = now()->format('Y-m');
    $lastMonth = now()->subMonth()->format('Y-m');

    Transaction::factory()->count(2)->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => now()->toDateString(),
    ]);

    Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => now()->subMonth()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get(
        "/api/v1/workspaces/{$this->workspace->id}/reports/export/csv?month={$thisMonth}"
    );

    $response->assertSuccessful();

    $lines = explode("\n", trim($response->getContent()));
    // Header row + 2 this-month rows
    expect(count($lines))->toBe(3);
});

it('returns 422 for invalid month format in monthly report', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/reports/monthly?month=not-a-month"
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
});

it('returns 401 when unauthenticated', function () {
    $response = $this->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/reports/monthly"
    );

    $response->assertUnauthorized();
});
