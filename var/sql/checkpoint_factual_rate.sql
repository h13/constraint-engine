SELECT
    DATE(date_created) as "date",
    COUNT(*) as "total",
    SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) as "factualCount",
    CASE WHEN COUNT(*) > 0
        THEN ROUND(CAST(SUM(CASE WHEN tag = 'factual' THEN 1 ELSE 0 END) AS FLOAT) / COUNT(*) * 100, 1)
        ELSE 0
    END as "factualRate"
FROM checkpoint
WHERE date_created BETWEEN :periodStart AND :periodEnd
GROUP BY DATE(date_created)
ORDER BY DATE(date_created)
