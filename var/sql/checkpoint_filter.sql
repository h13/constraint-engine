SELECT id, session_id, task_context, ai_proposal, human_final, diff, tag, confidence, date_created
FROM checkpoint
WHERE (:tag = '' OR tag = :tag)
  AND (:sessionId = '' OR session_id = :sessionId)
ORDER BY date_created DESC
