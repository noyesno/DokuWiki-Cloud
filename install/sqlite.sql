
CREATE TABLE `data` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `pages` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `meta` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `attic` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `cache` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `conf` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);


CREATE TABLE `index` (
  `id` integer PRIMARY KEY NOT NULL, 
  `path` text NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);



-- CREATE TABLE `memory` (
--   `id` integer PRIMARY KEY NOT NULL, 
--   `path` text NOT NULL,
--   `mtime` integer NOT NULL DEFAULT 0,
--   `ctime` integer NOT NULL DEFAULT 0, 
--   `data` text NOT NULL
-- );
