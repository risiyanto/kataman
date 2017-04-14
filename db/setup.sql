CREATE TABLE ajza (
        Juz INTEGER,
        Gid INTEGER,
        Qari VARCHAR(16) NOT NULL,
	Page INTEGER DEFAULT 0,
        Khatam INTEGER DEFAULT 0,
 	Edition TIMESTAMP DEFAULT 0, 
        Created TIMESTAMP
           DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime')),
        Updated TIMESTAMP
           DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);
CREATE TABLE editions (
        Gid INTEGER,
        Edition TIMESTAMP
           DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);
INSERT INTO "editions" VALUES(1,1492077924);
INSERT INTO "editions" VALUES(2,1492077991);
INSERT INTO "editions" VALUES(3,1492078375);
CREATE TABLE groups (
Gid INTEGER PRIMARY KEY ASC,
   Grp VARCHAR(32) UNIQUE NOT NULL,
   Info TEXT,
   HashPass VARCHAR(64) NOT NULL,
   Mail VARCHAR(64) NOT NULL,
   Private INTEGER DEFAULT 0,
   Created TIMESTAMP
   DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime')),
   Updated TIMESTAMP
   DEFAULT (strftime('%s',CURRENT_TIMESTAMP,'localtime'))
);
INSERT INTO "groups" VALUES(1,'tertutup','Group tertutup','$2y$10$7bMWQrpPGM4ZklfESmXZ1.I8gVm4q05yTy2ZFNjqOeDNx8A6URvOW','risiyanto@bekas.com',1,1492077924,1492077924);
INSERT INTO "groups" VALUES(2,'terbuka','Group terbuka','$2y$10$wxmQHfK10kJqNxYSXB7yKOc7/CHwgj7Kjc4fvqvUF/7fB1dLAGR7i','risiyanto@bekas.com',0,1492077991,1492077991);
INSERT INTO "groups" VALUES(3,'bebas','Group bebas','$2y$10$lvcRJRvozffOZnytX25AqOAzQoCUbfTLhttn6nyDyL7gKs.4ck9z2','risiyanto@bekas.com',0,1492078375,1492078375);
CREATE INDEX idx_ajza_gid_juz ON ajza (Gid, Juz, Edition);
CREATE TRIGGER new_edition_groups AFTER INSERT ON groups  
BEGIN  
INSERT INTO editions(Gid, Edition)  
         VALUES(NEW.Gid,NEW.Created);  
END;
