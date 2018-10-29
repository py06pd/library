ALTER TABLE users ADD roles character varying(256) null;

UPDATE users set roles = '["' || role || '","ROLE_ANONYMOUS"]' where role = 'ROLE_USER';
UPDATE users set roles = '["' || role || '","ROLE_USER","ROLE_ANONYMOUS"]' where role = 'ROLE_ADMIN';

ALTER TABLE users ALTER COLUMN roles SET not null;

ALTER TABLE users DROP COLUMN role;

CREATE TABLE public.user_sessions
(
    session_id character varying(256) NOT NULL,
    user_id integer NOT NULL,
    created timestamp NOT NULL,
    last_accessed timestamp NOT NULL,
    disabled boolean NOT NULL,
    disabled_reason integer,
    device character varying(256),
    PRIMARY KEY (session_id)
)