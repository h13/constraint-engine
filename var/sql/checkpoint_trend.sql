SELECT
    tag,
    CAST(date_created AS DATE) as date,
    COUNT(*) as count
FROM checkpoint
WHERE date_created BETWEEN :periodStart AND :periodEnd
GROUP BY tag, CAST(date_created AS DATE)
ORDER BY date
