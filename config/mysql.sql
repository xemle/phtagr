USE phtagr2;

CREATE TABLE images (
  id        INT NOT NULL AUTO_INCREMENT,
  modified  DATETIME NOT NULL,
  created   DATETIME NOT NULL,
  
  user_id   INT NOT NULL,
  group_id  INT NOT NULL DEFAULT 0,

  path      TEXT,
  file      TEXT,
  size      INT,
  flag      TINYINT UNSIGNED DEFAULT 0,
  
  name      VARCHAR(64) NOT NULL,
  date      DATETIME,
  width     INT UNSIGNED,
  height    INT UNSIGNED,
  duration  INT DEFAULT 0,
  orientation TINYINT UNSIGNED DEFAULT 0,
  
  caption   TEXT DEFAULT NULL,

  INDEX(id),
  INDEX(date),
  PRIMARY KEY(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE users (
  id        INT NOT NULL AUTO_INCREMENT,
  modified  DATETIME NOT NULL,
  created   DATETIME NOT NULL,
  expires   DATETIME,

  name      VARCHAR(32) NOT NULL,
  password  VARCHAR(128) NOT NULL,
  role      TINYINT UNSIGNED DEFAULT 0,
  
  PRIMARY KEY(id) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
