SELECT
    tag,
    DATE(date_created) as date,
    COUNT(*) as count
FROM checkpoint
WHERE date_created BETWEEN :periodStart AND :periodEnd
GROUP BY tag, DATE(date_created)
ORDER BY date
