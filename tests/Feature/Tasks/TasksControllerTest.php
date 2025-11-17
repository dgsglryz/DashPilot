<?php
declare(strict_types=1);

namespace Tests\Feature\Tasks;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TasksControllerTest tests task CRUD operations and Kanban board functionality.
 */
class TasksControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_index_requires_authentication(): void
    {
        $response = $this->get(route('tasks.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_tasks_index_displays_kanban_board(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['status' => 'pending']);
        Task::factory()->count(2)->create(['status' => 'in_progress']);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('tasks')
            ->has('stats')
        );
    }

    public function test_tasks_index_filters_my_tasks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Task::factory()->create(['assigned_to' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['assigned_to' => $otherUser->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->get(route('tasks.index', ['my_tasks' => true]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('tasks.pending')
        );
        
        // Verify only user's task is returned
        $pageData = $response->viewData('page');
        $pendingTasks = $pageData['props']['tasks']['pending'] ?? [];
        $this->assertCount(1, $pendingTasks);
        $this->assertEquals($user->id, $pendingTasks[0]['assignee']['id']);
    }

    public function test_task_create_page_displays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('developers')
            ->has('sites')
            ->has('clients')
        );
    }

    public function test_task_store_creates_new_task(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create(['role' => 'developer']);

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'New Task',
            'description' => 'Task description',
            'assigned_to' => $assignee->id,
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_task_show_displays_task_details(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('task')
        );
    }

    public function test_task_edit_page_displays(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('task')
            ->has('developers')
            ->has('sites')
            ->has('clients')
        );
    }

    public function test_task_update_modifies_existing_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['title' => 'Old Title']);
        $assignee = User::factory()->create(['role' => 'developer']);

        $response = $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => 'Updated Title',
            'description' => $task->description,
            'assigned_to' => $assignee->id,
            'priority' => $task->priority,
            'status' => $task->status,
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_task_status_update(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->post(route('tasks.status', $task), [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_task_destroy_deletes_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}

