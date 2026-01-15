<?php

namespace Tests\Unit\Features\AiCoach;

use App\Features\AiCoach\Tools\WorkoutQueryTool;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkoutQueryToolTest extends TestCase
{
    use RefreshDatabase;

    protected WorkoutQueryTool $tool;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tool = new WorkoutQueryTool();
        $this->user = User::factory()->create(['id' => 123]);
    }

    public function test_it_only_allows_select_queries()
    {
        $result = $this->tool->executeSecureQuery('DROP TABLE users', $this->user);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Only SELECT queries are allowed for safety.', $result['error']);
    }

    public function test_it_blocks_sensitive_tables()
    {
        $result = $this->tool->executeSecureQuery('SELECT * FROM users', $this->user);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Access to table \'users\' is not allowed', $result['error']);

        $result = $this->tool->executeSecureQuery('SELECT * FROM password_reset_tokens', $this->user);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Access to table \'password_reset_tokens\' is not allowed', $result['error']);
    }

    public function test_it_requires_an_allowed_table()
    {
        $result = $this->tool->executeSecureQuery('SELECT 1', $this->user);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Query must access at least one allowed table', $result['error']);
    }

    public function test_it_scopes_queries_to_user_id()
    {
        // Simple query without WHERE
        $query = "SELECT * FROM workouts";
        $scoped = $this->invokePrivateMethod($this->tool, 'addUserScopeToQuery', [$query, 123]);
        $this->assertEquals("SELECT * FROM workouts WHERE user_id = 123", $scoped);

        // Query with WHERE
        $query = "SELECT * FROM workouts WHERE title = 'Legs'";
        $scoped = $this->invokePrivateMethod($this->tool, 'addUserScopeToQuery', [$query, 123]);
        $this->assertEquals("SELECT * FROM workouts WHERE user_id = 123 AND title = 'Legs'", $scoped);

        // Query with ORDER BY
        $query = "SELECT * FROM workouts ORDER BY date DESC";
        $scoped = $this->invokePrivateMethod($this->tool, 'addUserScopeToQuery', [$query, 123]);
        $this->assertEquals("SELECT * FROM workouts WHERE user_id = 123 ORDER BY date DESC", $scoped);
        
        // Query with LIMIT
        $query = "SELECT * FROM workouts LIMIT 5";
        $scoped = $this->invokePrivateMethod($this->tool, 'addUserScopeToQuery', [$query, 123]);
        $this->assertEquals("SELECT * FROM workouts WHERE user_id = 123 LIMIT 5", $scoped);
    }

    /**
     * Helper to test protected methods
     */
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
