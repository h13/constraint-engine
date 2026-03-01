SELECT
    user_id,
    COUNT(*) as checkpoint_count,
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as factual_count,
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as strategic_count,
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as stylistic_count,
    MIN(date_created) as first_checkpoint,
    MAX(date_created) as last_checkpoint
FROM checkpoint
GROUP BY user_id
ORDER BY checkpoint_count DESC
