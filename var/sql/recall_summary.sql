SELECT
    SUM(CASE WHEN type = 'recall' THEN 1 ELSE 0 END) as "recallCount",
    SUM(CASE WHEN type = 'discovery' THEN 1 ELSE 0 END) as "discoveryCount",
    SUM(CASE WHEN type = 'friction' THEN 1 ELSE 0 END) as "frictionCount"
FROM checkpoint_recall
