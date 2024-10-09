INSERT INTO `schemaversion` (versionnumber) values ("3.2");

-- Define helper function to alter coordinates
DROP FUNCTION IF EXISTS `swap_wkt_coords`;

DELIMITER |
CREATE FUNCTION IF NOT EXISTS `swap_wkt_coords`(str TEXT) RETURNS text CHARSET utf8 COLLATE utf8_general_ci
BEGIN 
 DECLARE latStart, latEnd, lngStart, lngEnd, i INT;
 DECLARE cha CHAR;
 DECLARE flipped TEXT;

 SET i = 0;
 SET flipped = '';

 label1: LOOP
	SET i = i + 1;
    IF i <= LENGTH(str) THEN
      SET cha = SUBSTRING(str, i, 1);
     
      IF cha REGEXP '^[A-Za-z(),]' THEN
      	IF latStart is not null and latEnd is not null and lngStart is not null THEN
      		SET lngEnd = i;
      		SET flipped = CONCAT(flipped, 
      		SUBSTRING(str, lngStart, CASE WHEN lngStart = lngEnd THEN 1 ELSE lngEnd - lngStart END),
	      	" ", 
	      	SUBSTRING(str, latStart, CASE WHEN latStart = latEnd THEN 1 ELSE latEnd - latStart END)
	      );	     
      	END IF;
      	-- SET flipped = CONCAT(flipped, lngEnd);
      	SET flipped = CONCAT(flipped, cha);
      	SET latStart = null, latEnd = null, lngStart = null, lngEnd = null;
      ELSEIF cha = " " THEN
      	IF latStart is not null THEN
      		SET latEnd = i;
      	    -- SET flipped = CONCAT(flipped, latEnd);
      	ELSE
      		SET flipped = CONCAT(flipped, ' ');
      	END IF;
      ELSE
      	if latStart is null THEN
      		SET latStart = i;
      	    -- SET flipped = CONCAT(flipped, latStart);
      	ELSEIF latEnd is not null and lngStart is null THEN
      		SET lngStart = i;
      	    -- SET flipped = CONCAT(flipped, lngStart);
      	END IF;
      END IF;
      ITERATE label1;
    END IF;
    LEAVE label1;
  END LOOP label1;
 
  RETURN flipped;
END
|
DELIMITER ;

-- Add and update checklist footprints to be geoJson
ALTER TABLE fmchecklists ADD COLUMN IF NOT EXISTS footprintGeoJson text;
UPDATE fmchecklists set footprintGeoJson = ST_ASGEOJSON(ST_GEOMFROMTEXT(swap_wkt_coords(footprintWkt))) where footprintGeoJson is null;

-- Remove wkt?

-- Removes All omoccurpoints that have null lat or lng values in omocurrences which is needed to recalculate all omoccurpoints into lnglat points
DELETE FROM omoccurpoints where occid in (SELECT o.occid from omoccurpoints o join omoccurrences o2 on o.occid = o2.occid where o2.decimalLatitude is null or o2.decimalLongitude is null); 

-- Create and add lng lat points for occurrence data which is needed to do searching is spacial indexes that are lng lat
ALTER TABLE omoccurpoints ADD COLUMN IF NOT EXISTS lngLatPoint POINT;
UPDATE omoccurpoints p join omoccurrences o on o.occid = p.occid set lngLatPoint = ST_POINTFROMTEXT(CONCAT('POINT(',o.decimalLongitude, ' ', o.decimalLatitude, ')'));  
ALTER TABLE omoccurpoints MODIFY IF EXISTS lngLatPoint POINT NOT NULL;
ALTER TABLE omoccurpoints ADD SPATIAL INDEX(lngLatPoint);

DROP FUNCTION IF EXISTS `swap_wkt_coords`;
