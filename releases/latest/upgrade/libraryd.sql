ALTER TABLE books ADD creator_id integer null;
UPDATE books SET creator_id = 3;
ALTER TABLE books ALTER COLUMN creator_id set not null;

UPDATE users set roles = '["ROLE_LIBRARIAN","ROLE_USER","ROLE_ANONYMOUS"]' where roles = '["ROLE_USER","ROLE_ANONYMOUS"]';
UPDATE users set roles = '["ROLE_ADMIN","ROLE_LIBRARIAN","ROLE_USER","ROLE_ANONYMOUS"]' where roles = '["ROLE_ADMIN","ROLE_USER","ROLE_ANONYMOUS"]';