
CREATE TABLE `data` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `pages` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `meta` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `attic` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `cache` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);

CREATE TABLE `conf` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);


CREATE TABLE `index` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);



CREATE TABLE `memory` (
  `path` text PRIMARY KEY NOT NULL,
  `mtime` integer NOT NULL DEFAULT 0,
  `ctime` integer NOT NULL DEFAULT 0, 
  `data` text NOT NULL
);
