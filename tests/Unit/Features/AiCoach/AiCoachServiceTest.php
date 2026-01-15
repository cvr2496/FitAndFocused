<?php

namespace Tests\Unit\Features\AiCoach;

use App\Features\AiCoach\AiCoachService;
use App\Features\AiCoach\Tools\WorkoutQueryTool;
use App\Services\AnthropicService;
use App\Models\User;
use Tests\TestCase;
use Mockery;

class AiCoachServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_be_instantiated()
    {
        $ai = Mockery::mock(AnthropicService::class);
        $tool = Mockery::mock(WorkoutQueryTool::class);
        $service = new AiCoachService($ai, $tool);
        
        $this->assertInstanceOf(AiCoachService::class, $service);
    }

    public function test_generate_recommendation_calls_ai_provider()
    {
        $ai = Mockery::mock(AnthropicService::class);
        $tool = Mockery::mock(WorkoutQueryTool::class);
        $user = new User(['id' => 1]);
        
        $service = new AiCoachService($ai, $tool);

        $ai->shouldReceive('executeToolLoop')
            ->once()
            ->andReturn(json_encode([
                'title' => 'Test Workout',
                'description' => 'Test description',
                'exercises' => []
            ]));

        $result = $service->generateRecommendation($user);

        $this->assertEquals('Test Workout', $result['title']);
    }

    public function test_chat_requires_authenticated_user()
    {
        $ai = Mockery::mock(AnthropicService::class);
        $tool = Mockery::mock(WorkoutQueryTool::class);
        $service = new AiCoachService($ai, $tool);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User must be authenticated to use AI chat');

        $service->chat('Hello');
    }

    public function test_chat_calls_ai_provider()
    {
        $ai = Mockery::mock(AnthropicService::class);
        $tool = Mockery::mock(WorkoutQueryTool::class);
        $user = new User(['id' => 1]);
        $service = new AiCoachService($ai, $tool);

        $ai->shouldReceive('executeToolLoop')
            ->once()
            ->andReturn('Hello from AI');

        $result = $service->chat('Hi there', [], $user);

        $this->assertEquals('Hello from AI', $result);
    }
}
