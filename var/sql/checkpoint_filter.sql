SELECT
    id as "checkpointId",
    session_id as "sessionId",
    task_context as "taskContext",
    tag,
    confidence,
    date_created as "dateCreated"
FROM checkpoint
WHERE (:tag = '' OR tag = :tag)
  AND (:sessionId = '' OR session_id = :sessionId)
ORDER BY date_created DESC
LIMIT 200
