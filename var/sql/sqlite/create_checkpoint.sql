CREATE TABLE IF NOT EXISTS checkpoint (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    task_context TEXT NOT NULL,
    ai_proposal TEXT NOT NULL,
    human_final TEXT NOT NULL,
    diff TEXT NOT NULL,
    tag TEXT NOT NULL CHECK(tag IN ('factual', 'strategic', 'stylistic')),
    confidence TEXT NOT NULL CHECK(confidence IN ('estimated', 'stated')),
    date_created TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_checkpoint_tag ON checkpoint (tag);
CREATE INDEX IF NOT EXISTS idx_checkpoint_session ON checkpoint (session_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_date ON checkpoint (date_created);

CREATE TABLE IF NOT EXISTS checkpoint_recall (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    checkpoint_id INTEGER NOT NULL REFERENCES checkpoint(id),
    type TEXT NOT NULL CHECK(type IN ('recall', 'discovery', 'friction')),
    note TEXT NOT NULL DEFAULT '',
    date_created TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_recall_checkpoint ON checkpoint_recall (checkpoint_id);
CREATE INDEX IF NOT EXISTS idx_recall_type ON checkpoint_recall (type);
