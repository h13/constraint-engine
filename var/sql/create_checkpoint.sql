CREATE TABLE IF NOT EXISTS checkpoint (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    task_context TEXT NOT NULL,
    ai_proposal TEXT NOT NULL,
    human_final TEXT NOT NULL,
    diff TEXT NOT NULL,
    tag TEXT NOT NULL,
    confidence TEXT NOT NULL,
    date_created TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_checkpoint_tag ON checkpoint (tag);
CREATE INDEX IF NOT EXISTS idx_checkpoint_session ON checkpoint (session_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_date ON checkpoint (date_created);
