SELECT id, session_id, task_context, ai_proposal, human_final, diff, tag, confidence, date_created
FROM checkpoint
ORDER BY date_created DESC
