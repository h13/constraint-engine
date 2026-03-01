SELECT
    session_id,
    task_context,
    COUNT(*) as checkpoint_count,
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as factual_count,
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as strategic_count,
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as stylistic_count
FROM checkpoint
WHERE session_id = :sessionId
GROUP BY session_id, task_context
