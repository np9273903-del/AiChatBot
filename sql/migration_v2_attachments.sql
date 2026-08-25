-- Run this once against an existing soen_chat database created from schema.sql.
-- Adds: voice-note / image / file attachments, and a per-browser-tab client id
-- used to fix the "message appears twice when I have two tabs open" edge case
-- (the SSE stream used to dedupe by user_id, which two tabs of the same user share).

USE soen_chat;

ALTER TABLE messages
    ADD COLUMN client_id VARCHAR(40) NULL AFTER user_id,
    ADD COLUMN attachment_url VARCHAR(255) NULL AFTER message,
    ADD COLUMN attachment_type ENUM('image','audio','file') NULL AFTER attachment_url;
