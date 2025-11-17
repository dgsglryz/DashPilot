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
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'New Task',
            'description' => 'Task description',
            'client_id' => $client->id,
            'assigned_to' => $user->id, // Non-admin can only assign to themselves
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_task_show_displays_task_details(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('task')
        );
    }

    public function test_task_edit_page_displays(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['client_id' => $client->id]);

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
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['title' => 'Old Title', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => 'Updated Title',
            'description' => $task->description,
            'client_id' => $client->id,
            'assigned_to' => $user->id, // Non-admin can only assign to themselves
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
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['status' => 'pending', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->post(route('tasks.status', $task), [
            'status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_task_destroy_moves_task_to_cancelled(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['status' => 'pending', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_tasks_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Task::factory()->create(['status' => 'pending', 'client_id' => $client->id]);
        Task::factory()->create(['status' => 'in_progress', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('tasks.index', ['status' => 'pending']));

        $response->assertOk();
        $pageData = $response->viewData('page');
        $pendingTasks = $pageData['props']['tasks']['pending'] ?? [];
        $this->assertCount(1, $pendingTasks);
    }

    public function test_tasks_index_filters_by_priority(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Task::factory()->create(['priority' => 'high', 'client_id' => $client->id]);
        Task::factory()->create(['priority' => 'low', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('tasks.index', ['priority' => 'high']));

        $response->assertOk();
    }

    public function test_tasks_index_filters_by_urgent(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Task::factory()->create(['priority' => 'urgent', 'client_id' => $client->id]);
        Task::factory()->create(['priority' => 'low', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('tasks.index', ['urgent' => true]));

        $response->assertOk();
        $pageData = $response->viewData('page');
        $stats = $pageData['props']['stats'] ?? [];
        $this->assertGreaterThanOrEqual(1, $stats['urgent'] ?? 0);
    }

    public function test_tasks_index_searches_by_query(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        Task::factory()->create(['title' => 'Find this task', 'client_id' => $client->id]);
        Task::factory()->create(['title' => 'Other task', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('tasks.index', ['query' => 'Find']));

        $response->assertOk();
    }

    public function test_get_user_tasks_returns_tasks_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Task::factory()->count(3)->create(['assigned_to' => $user->id]);
        Task::factory()->count(2)->create(['assigned_to' => $otherUser->id]);

        $response = $this->actingAs($user)->getJson(route('tasks.user', $user));

        $response->assertOk()
            ->assertJsonStructure([
                'tasks',
                'stats',
                'user',
            ]);

        $data = $response->json();
        $this->assertCount(3, $data['tasks']);
        $this->assertEquals($user->id, $data['user']['id']);
    }

    public function test_task_status_update_sets_completed_at_when_completed(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create(['status' => 'pending', 'client_id' => $client->id]);

        $response = $this->actingAs($user)->post(route('tasks.status', $task), [
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $task->refresh();
        $this->assertNotNull($task->completed_at);
    }

    public function test_task_status_update_clears_completed_at_when_uncompleted(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $task = Task::factory()->create([
            'status' => 'completed',
            'completed_at' => now(),
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($user)->post(route('tasks.status', $task), [
            'status' => 'pending',
        ]);

        $response->assertRedirect();
        $task->refresh();
        $this->assertNull($task->completed_at);
    }

    // ========== AUTHORIZATION TESTS ==========

    /**
     * Test that user cannot view tasks from unassigned clients.
     */
    public function test_user_cannot_view_tasks_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        $task1 = Task::factory()->create(['client_id' => $client1->id]);
        $task2 = Task::factory()->create(['client_id' => $client2->id]);

        // User1 should only see task1
        $response = $this->actingAs($user1)->get(route('tasks.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $allTasks = collect($pageData['props']['tasksPaginated']['data'] ?? []);
        $taskIds = $allTasks->pluck('id')->toArray();
        
        $this->assertContains($task1->id, $taskIds);
        $this->assertNotContains($task2->id, $taskIds);
    }

    /**
     * Test that user cannot view a specific task from unassigned client.
     */
    public function test_user_cannot_view_task_from_unassigned_client(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $task = Task::factory()->create(['client_id' => $client2->id]);

        $response = $this->actingAs($user1)->get(route('tasks.show', $task));

        $response->assertForbidden();
    }

    /**
     * Test that user cannot update tasks from unassigned clients.
     */
    public function test_user_cannot_update_tasks_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $task = Task::factory()->create(['client_id' => $client2->id, 'title' => 'Original Title']);

        $response = $this->actingAs($user1)->put(route('tasks.update', $task), [
            'title' => 'Hacked Title',
            'description' => $task->description,
            'assigned_to' => $task->assigned_to,
            'priority' => $task->priority,
            'status' => $task->status,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original Title',
        ]);
    }

    /**
     * Test that user cannot delete tasks from unassigned clients.
     */
    public function test_user_cannot_delete_tasks_from_unassigned_clients(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $task = Task::factory()->create(['client_id' => $client2->id]);

        $response = $this->actingAs($user1)->delete(route('tasks.destroy', $task));

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    /**
     * Test that user can view tasks assigned directly to them.
     */
    public function test_user_can_view_tasks_assigned_to_them(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $task = Task::factory()->create([
            'client_id' => $client2->id,
            'assigned_to' => $user1->id, // Task assigned to user1
        ]);

        // User1 should be able to view task even though client is assigned to user2
        $response = $this->actingAs($user1)->get(route('tasks.show', $task));

        $response->assertOk();
    }

    /**
     * Test that admin can view all tasks.
     */
    public function test_admin_can_view_all_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client1 = Client::factory()->create(['assigned_developer_id' => $user1->id]);
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        
        $task1 = Task::factory()->create(['client_id' => $client1->id]);
        $task2 = Task::factory()->create(['client_id' => $client2->id]);

        $response = $this->actingAs($admin)->get(route('tasks.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $allTasks = collect($pageData['props']['tasksPaginated']['data'] ?? []);
        $taskIds = $allTasks->pluck('id')->toArray();
        
        $this->assertContains($task1->id, $taskIds);
        $this->assertContains($task2->id, $taskIds);
    }

    // ========== DATA SCOPING TESTS ==========

    /**
     * Test that tasks index only shows tasks from assigned clients.
     */
    public function test_tasks_index_only_shows_assigned_client_tasks(): void
    {
        $user = User::factory()->create(['role' => 'developer']);
        $otherUser = User::factory()->create(['role' => 'developer']);
        
        $assignedClient = Client::factory()->create(['assigned_developer_id' => $user->id]);
        $unassignedClient = Client::factory()->create(['assigned_developer_id' => $otherUser->id]);
        
        $assignedTask = Task::factory()->create(['client_id' => $assignedClient->id]);
        $unassignedTask = Task::factory()->create(['client_id' => $unassignedClient->id]);

        $response = $this->actingAs($user)->get(route('tasks.index'));
        $response->assertOk();
        
        $pageData = $response->viewData('page');
        $allTasks = collect($pageData['props']['tasksPaginated']['data'] ?? []);
        $taskIds = $allTasks->pluck('id')->toArray();
        
        $this->assertContains($assignedTask->id, $taskIds);
        $this->assertNotContains($unassignedTask->id, $taskIds);
    }

    /**
     * Test that user cannot update task status for unassigned client task.
     */
    public function test_user_cannot_update_status_for_unassigned_client_task(): void
    {
        $user1 = User::factory()->create(['role' => 'developer']);
        $user2 = User::factory()->create(['role' => 'developer']);
        
        $client2 = Client::factory()->create(['assigned_developer_id' => $user2->id]);
        $task = Task::factory()->create(['client_id' => $client2->id, 'status' => 'pending']);

        $response = $this->actingAs($user1)->post(route('tasks.status', $task), [
            'status' => 'completed',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pending',
        ]);
    }
}

