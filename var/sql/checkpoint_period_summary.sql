SELECT
    COUNT(*) as "totalCheckpoints",
    COALESCE(SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END), 0) as "factualCount",
    COALESCE(SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END), 0) as "strategicCount",
    COALESCE(SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END), 0) as "stylisticCount"
FROM checkpoint
WHERE date_created >= :periodStart
  AND date_created < :periodEnd
