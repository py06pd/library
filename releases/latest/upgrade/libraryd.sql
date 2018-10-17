ALTER TABLE userbook ALTER COLUMN requestedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN borrowedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN giftfromid drop not null;

UPDATE userbook SET requestedfromid = null where requestedfromid = 0;
UPDATE userbook SET borrowedfromid = null where borrowedfromid = 0;
UPDATE userbook SET giftfromid = null where giftfromid = 0;

UPDATE users SET "role" = 'ROLE_ADMIN' WHERE "role" = 'admin';
UPDATE users SET "role" = 'ROLE_USER' WHERE "role" = 'anon';

CREATE TABLE groupuser
(
    id integer NOT NULL,
    userid integer NOT NULL,
    CONSTRAINT groupuser_pkey PRIMARY KEY (id, userid)
);

CREATE TABLE user_group
(
    group_id serial NOT NULL,
    group_name character varying(64) NOT NULL,
    CONSTRAINT user_group_pkey PRIMARY KEY (group_id)
);