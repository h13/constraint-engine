SELECT
    session_id as "sessionId",
    (SELECT task_context FROM checkpoint c2
     WHERE c2.session_id = checkpoint.session_id
     ORDER BY c2.date_created ASC LIMIT 1) as "taskContext",
    COUNT(*) as "checkpointCount",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as "strategicCount",
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as "stylisticCount",
    MIN(date_created) as "firstCheckpoint",
    MAX(date_created) as "lastCheckpoint"
FROM checkpoint
GROUP BY session_id
ORDER BY MAX(date_created) DESC
