CREATE TABLE notify_client (
   id TEXT PRIMARY KEY,
   app INTEGER NOT NULL,
      -- 0 = Win/Mac notifier
      -- 1 = iOS
   name TEXT NOT NULL,
   username TEXT NULL
);

CREATE TABLE notify_user (
   username TEXT PRIMARY KEY,
   match_replies BOOLEAN NOT NULL,
   match_mentions BOOLEAN NOT NULL
);

CREATE TABLE notify_user_keyword (
   username TEXT,
   keyword TEXT,
   PRIMARY KEY (username, keyword)
);

CREATE TABLE notify_client_queue (
   id SERIAL PRIMARY KEY,
   client_id TEXT NOT NULL REFERENCES notify_client ON DELETE CASCADE,
   subject TEXT NOT NULL,
   body TEXT NOT NULL,
   post_id INTEGER NOT NULL,
   thread_id INTEGER NOT NULL,
   expiration TIMESTAMP NOT NULL
);

CREATE INDEX idx_notify_client_queue_client_id ON notify_client_queue (client_id);
