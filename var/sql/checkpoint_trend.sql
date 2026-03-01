SELECT
    tag,
    CAST(date_created AS DATE) as date,
    COUNT(*) as count
FROM checkpoint
WHERE date_created >= :periodStart
  AND date_created < :periodEnd
GROUP BY tag, CAST(date_created AS DATE)
ORDER BY CAST(date_created AS DATE)
