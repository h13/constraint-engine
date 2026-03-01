SELECT
    COUNT(*) as "totalCheckpoints",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    SUM(CASE WHEN tag = 'strategic' THEN 1 ELSE 0 END) as "strategicCount",
    SUM(CASE WHEN tag = 'stylistic' THEN 1 ELSE 0 END) as "stylisticCount"
FROM checkpoint
WHERE date_created >= :periodStart
  AND date_created < :periodEnd
