ALTER TABLE books ADD type_id INT DEFAULT NULL;

CREATE TABLE genres
(
    genre_id serial NOT NULL,
    name character varying(256) NOT NULL,
    CONSTRAINT genres_pkey PRIMARY KEY (genre_id)
);

CREATE TABLE book_genre
(
    book_id integer NOT NULL,
    genre_id integer NOT NULL,
    CONSTRAINT bookgenre_pkey PRIMARY KEY (book_id, genre_id)
);

insert into genres (genre_id, name)
select nextval('genres_genre_id_seq'), substring(genreName,2,length(genreName) - 2) from (
select distinct cast(genre as varchar) as genreName from (
SELECT json_array_elements(cast(genres as json)) as genre
from books where genres is not null) as selection) as selection2;

insert into book_genre
select s.id, g.genre_id from (
	SELECT b.id, json_array_elements(cast(genres as json)) as genre
	from books b
	where genres is not null
) as s
join genres g on '"' || g.name || '"' = cast(s.genre as varchar);

CREATE TABLE types
(
    type_id serial NOT NULL,
    name character varying(256) NOT NULL,
    CONSTRAINT types_pkey PRIMARY KEY (type_id)
);

INSERT INTO types (type_id, name)
SELECT nextval('types_type_id_seq'), type
FROM (SELECT DISTINCT type FROM books where type is not null and type != '') as selection;

update books set type_id = types.type_id from types
where books.type = types.name;