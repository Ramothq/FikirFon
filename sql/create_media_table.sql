CREATE TABLE media (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    idea_id INTEGER REFERENCES ideas(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INTEGER NOT NULL,
    s3_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- İndeksler
CREATE INDEX idx_media_user_id ON media(user_id);
CREATE INDEX idx_media_idea_id ON media(idea_id);
CREATE INDEX idx_media_s3_key ON media(s3_key); 