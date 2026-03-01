SELECT
    SUM(CASE WHEN type = 'recall' THEN 1 ELSE 0 END) as recall_count,
    SUM(CASE WHEN type = 'discovery' THEN 1 ELSE 0 END) as discovery_count,
    SUM(CASE WHEN type = 'friction' THEN 1 ELSE 0 END) as friction_count
FROM checkpoint_recall
