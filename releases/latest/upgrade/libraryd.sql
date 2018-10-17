ALTER TABLE userbook ALTER COLUMN requestedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN borrowedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN giftfromid drop not null;

UPDATE users SET "role" = 'ROLE_ADMIN' WHERE "role" = 'admin';
UPDATE users SET "role" = 'ROLE_USER' WHERE "role" = 'anon';