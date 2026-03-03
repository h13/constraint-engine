CREATE TABLE IF NOT EXISTS checkpoint (
    id SERIAL PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL DEFAULT 'default',
    session_id VARCHAR(255) NOT NULL,
    task_context TEXT NOT NULL,
    ai_proposal TEXT NOT NULL,
    human_final TEXT NOT NULL,
    diff TEXT NOT NULL,
    tag VARCHAR(20) NOT NULL CHECK(tag IN ('factual', 'strategic', 'stylistic')),
    confidence VARCHAR(20) NOT NULL CHECK(confidence IN ('estimated', 'stated')),
    date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_checkpoint_tag ON checkpoint (tag);
CREATE INDEX IF NOT EXISTS idx_checkpoint_session ON checkpoint (session_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_date ON checkpoint (date_created);
CREATE INDEX IF NOT EXISTS idx_checkpoint_user ON checkpoint (user_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_tag_session ON checkpoint (tag, session_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_date_tag ON checkpoint (date_created, tag);

CREATE TABLE IF NOT EXISTS checkpoint_recall (
    id SERIAL PRIMARY KEY,
    checkpoint_id INTEGER NOT NULL REFERENCES checkpoint(id),
    type VARCHAR(20) NOT NULL CHECK(type IN ('recall', 'discovery', 'friction')),
    note TEXT NOT NULL DEFAULT '',
    date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_recall_checkpoint ON checkpoint_recall (checkpoint_id);
CREATE INDEX IF NOT EXISTS idx_recall_type ON checkpoint_recall (type);
