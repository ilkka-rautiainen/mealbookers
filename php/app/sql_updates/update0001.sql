CREATE TABLE meals
(
  id serial NOT NULL,
  "name" text NOT NULL,
  "language" text NOT NULL,
  restaurant_id integer NOT NULL,
  "day" date NOT NULL,
  section text,
  CONSTRAINT meals_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

CREATE TABLE restaurants
(
  id serial NOT NULL,
  "name" text NOT NULL,
  street_address text,
  coord_lat double precision,
  coord_long double precision,
  opening_hours text[],
  lunch_hours text[],
  CONSTRAINT restaurants_pkey PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

INSERT INTO restaurants(
            "name", street_address, coord_lat, coord_long, opening_hours, 
            lunch_hours)
    VALUES ('Alvari', 'Otakaari 1', 22, 11, '{}', 
            '{}');
