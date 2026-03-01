SELECT
    user_id as "userId",
    COUNT(*) as "checkpointCount",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as "strategicCount",
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as "stylisticCount",
    MIN(date_created) as "firstCheckpoint",
    MAX(date_created) as "lastCheckpoint"
FROM checkpoint
GROUP BY user_id
ORDER BY COUNT(*) DESC
