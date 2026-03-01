SELECT
    id as "checkpointId",
    session_id as "sessionId",
    task_context as "taskContext",
    tag,
    confidence,
    date_created as "dateCreated"
FROM checkpoint
ORDER BY date_created DESC
