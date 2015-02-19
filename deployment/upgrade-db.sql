-- This script will migrate your database to the current version.
-- The version number is stored as a comment on the 'indexer' table.
-- 
-- Instructions:
--   psql -h 127.1 -f upgrade-db.sql chatty nusearch
--   (enter the password 'nusearch')

BEGIN TRANSACTION;
DO $$
   BEGIN

   RAISE NOTICE 'Old version: %', obj_description('indexer'::regclass, 'pg_class');

   IF obj_description('indexer'::regclass, 'pg_class') IS NULL THEN
      RAISE NOTICE 'Upgrading to version 1...';
      IF NOT EXISTS (SELECT 0 FROM pg_class WHERE relname = 'post_lols') THEN
         CREATE TABLE post_lols (
            post_id INTEGER NOT NULL,
            tag TEXT NOT NULL,
            count INTEGER NOT NULL,
            PRIMARY KEY (post_id, tag)
         );
         CREATE INDEX idx_post_lols_post_id ON post_lols (post_id);
      END IF;
      COMMENT ON TABLE indexer IS '1';
   END IF;

   IF obj_description('indexer'::regclass, 'pg_class') = '1' THEN
      RAISE NOTICE 'Upgrading to version 2...';
      CREATE TABLE new_post_queue (
         id SERIAL PRIMARY KEY,
         username TEXT NOT NULL,
         parent_id INTEGER NOT NULL,
         body TEXT NOT NULL
      );
      COMMENT ON TABLE indexer IS '2';
   END IF;

   RAISE NOTICE 'New version: %', obj_description('indexer'::regclass, 'pg_class');

   END;
$$ LANGUAGE plpgsql;
COMMIT TRANSACTION;
