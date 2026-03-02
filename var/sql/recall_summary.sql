SELECT
    COALESCE(SUM(CASE WHEN type = 'recall' THEN 1 ELSE 0 END), 0) as "recallCount",
    COALESCE(SUM(CASE WHEN type = 'discovery' THEN 1 ELSE 0 END), 0) as "discoveryCount",
    COALESCE(SUM(CASE WHEN type = 'friction' THEN 1 ELSE 0 END), 0) as "frictionCount"
FROM checkpoint_recall
