SELECT
    id as "checkpointId",
    session_id as "sessionId",
    task_context as "taskContext",
    ai_proposal as "aiProposal",
    human_final as "humanFinal",
    diff,
    tag,
    confidence,
    date_created as "dateCreated"
FROM checkpoint
WHERE session_id = :sessionId
ORDER BY date_created ASC
LIMIT 500
