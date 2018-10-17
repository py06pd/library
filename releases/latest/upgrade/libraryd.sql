ALTER TABLE userbook ALTER COLUMN requestedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN borrowedfromid drop not null;
ALTER TABLE userbook ALTER COLUMN giftfromid drop not null;

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
    group_id series NOT NULL,
    group_name character varying(64) NOT NULL,
    CONSTRAINT user_group_pkey PRIMARY KEY (group_id)
);