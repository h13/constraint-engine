SELECT tag, COUNT(*) as "count"
FROM checkpoint
GROUP BY tag
