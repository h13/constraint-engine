SELECT
    id as "checkpointId",
    session_id as "sessionId",
    task_context as "taskContext",
    ai_proposal as "aiProposal",
    human_final as "humanFinal",
    diff,
    tag,
    confidence,
    user_id as "userId",
    date_created as "dateCreated"
FROM checkpoint
WHERE id = :id
