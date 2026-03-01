SELECT
    session_id as "sessionId",
    MAX(task_context) as "taskContext",
    COUNT(*) as "checkpointCount",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as "strategicCount",
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as "stylisticCount"
FROM checkpoint
WHERE session_id = :sessionId
GROUP BY session_id
