<?php

namespace App\Features\AiCoach\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkoutQueryTool
{
    /**
     * Whitelist of allowed tables that contain user-specific data
     */
    protected array $allowedTables = [
        'workouts',
        'exercises',
        'sets',
        'workout_exercises',
    ];

    /**
     * Block access to sensitive tables
     */
    protected array $blockedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'cache',
        'jobs',
        'failed_jobs',
    ];

    /**
     * Execute a read-only SQL query against the workouts database.
     * 
     * @param string $query
     * @param mixed $user
     * @return array
     */
    public function executeSecureQuery(string $query, $user): array
    {
        // Safety check: ensure only SELECT statements
        if (stripos(trim($query), 'SELECT') !== 0) {
            return ['error' => 'Only SELECT queries are allowed for safety.'];
        }

        // Check for blocked tables
        foreach ($this->blockedTables as $table) {
            if (stripos($query, $table) !== false) {
                return ['error' => "Access to table '{$table}' is not allowed for security reasons."];
            }
        }

        // Validate that query only accesses allowed tables
        $queryLower = strtolower($query);
        $hasAllowedTable = false;
        foreach ($this->allowedTables as $table) {
            if (stripos($queryLower, $table) !== false) {
                $hasAllowedTable = true;
                break;
            }
        }

        if (!$hasAllowedTable) {
            return ['error' => 'Query must access at least one allowed table: ' . implode(', ', $this->allowedTables)];
        }

        // Automatically scope the query to the authenticated user
        $scopedQuery = $this->addUserScopeToQuery($query, $user->id);

        try {
            Log::info("Executing AI-requested query for user {$user->id}", ['query' => $scopedQuery]);
            return DB::select($scopedQuery);
        } catch (\Exception $e) {
            Log::error("AI-requested query failed", ['error' => $e->getMessage(), 'query' => $scopedQuery]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Add user_id filtering to a SQL query to scope it to a specific user
     *
     * @param string $query
     * @param int $userId
     * @return string
     */
    protected function addUserScopeToQuery(string $query, int $userId): string
    {
        $query = trim($query);
        
        // Check if query already has WHERE clause
        if (stripos($query, 'WHERE') !== false) {
            // Add AND user_id = X to existing WHERE clause
            $query = preg_replace(
                '/(WHERE\s+)/i',
                "$1user_id = {$userId} AND ",
                $query,
                1
            );
        } else {
            // Add WHERE user_id = X before ORDER BY, LIMIT, or at the end
            if (preg_match('/(ORDER\s+BY|LIMIT|GROUP\s+BY)/i', $query, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1];
                $query = rtrim(substr($query, 0, $position)) . " WHERE user_id = {$userId} " . substr($query, $position);
            } else {
                // No ORDER BY or LIMIT, add at the end
                $query = rtrim(rtrim($query, ';')) . " WHERE user_id = {$userId}";
            }
        }
        
        return $query;
    }
}
