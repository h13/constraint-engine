SELECT
    CAST(date_created AS DATE) as "date",
    COUNT(*) as "total",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    CASE WHEN COUNT(*) > 0
        THEN ROUND(SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1)
        ELSE 0
    END as "factualRate"
FROM checkpoint
WHERE date_created >= :periodStart
  AND date_created < :periodEnd
GROUP BY CAST(date_created AS DATE)
ORDER BY CAST(date_created AS DATE)
