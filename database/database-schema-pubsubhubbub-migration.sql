
-- up
ALTER TABLE ssscrape_feed ADD COLUMN `hub` varchar(255) NULL COMMENT 'pubsubhubbub hub';
ALTER TABLE ssscrape_feed ADD COLUMN `subscription_state` enum('no','sub-requested', 'unsub-requested', 'subscribed') NOT NULL DEFAULT 'no';

-- down
ALTER TABLE ssscrape_feed DROP COLUMN `hub`;
ALTER TABLE ssscrape_feed DROP COLUMN `subscription_state`;
