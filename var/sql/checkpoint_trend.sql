SELECT
    tag,
    DATE(date_created) as "date",
    COUNT(*) as "count"
FROM checkpoint
WHERE date_created >= :periodStart
  AND date_created < :periodEnd
GROUP BY tag, DATE(date_created)
ORDER BY DATE(date_created)
