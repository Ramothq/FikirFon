-- Fikir destekleme tablosu
CREATE TABLE IF NOT EXISTS idea_supports (
    id SERIAL PRIMARY KEY,
    idea_id INTEGER NOT NULL REFERENCES ideas(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(idea_id, user_id)
);

-- Fikirler tablosuna destek sayısı sütunu ekleme
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS support_count INTEGER DEFAULT 0; 