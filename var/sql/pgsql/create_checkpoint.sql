CREATE TABLE IF NOT EXISTS checkpoint (
    id SERIAL PRIMARY KEY,
    session_id TEXT NOT NULL,
    task_context TEXT NOT NULL,
    ai_proposal TEXT NOT NULL,
    human_final TEXT NOT NULL,
    diff TEXT NOT NULL,
    tag TEXT NOT NULL CHECK(tag IN ('factual', 'strategic', 'stylistic')),
    confidence TEXT NOT NULL CHECK(confidence IN ('estimated', 'stated')),
    date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_checkpoint_tag ON checkpoint (tag);
CREATE INDEX IF NOT EXISTS idx_checkpoint_session ON checkpoint (session_id);
CREATE INDEX IF NOT EXISTS idx_checkpoint_date ON checkpoint (date_created);
