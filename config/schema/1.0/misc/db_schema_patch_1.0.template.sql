DROP PROCEDURE updateSymbiotaSchema;

DELIMITER |

CREATE PROCEDURE updateSymbiotaSchema ()

BEGIN
  DECLARE requiredVersion varchar(20);  -- version needed for update to fire
  DECLARE currentVersion varchar(20);   -- version present in schema
  DECLARE newVersion varchar(20);       -- version this update will apply
  DECLARE okToUpdate boolean DEFAULT FALSE;
  DECLARE done boolean DEFAULT FALSE;
  DECLARE curVersion CURSOR for select versionnumber from schemaversion order by dateapplied desc limit 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  --  (1)  Change the version numbers ****************************
  --  Previous version must be this version for the update to fire
  SET requiredVersion = '1.0.1.*';
  SET newVersion = '1.0.1.*';
  --  ************************************************************

  OPEN curVersion;
  
  verLoop: LOOP
     FETCH curVersion into currentVersion;
     IF done THEN
        LEAVE verLoop;
     END IF;
     IF currentVersion = requiredVersion THEN 
        SET okToUpdate = TRUE;
     END IF;
  END LOOP;

IF okToUpdate THEN 

START TRANSACTION;

INSERT INTO schemaversion (versionnumber) values (newVersion);

-- (2) ******** Fill in Schema Changes to be applied in this update *********************



--  ******* End of Schema Changes to be applied in this update 

COMMIT;

--  if in MySQL/MARIADB 5.2+ where SIGNAL is supported, can return an error condition
--  ELSE
   -- SIGNAL SQLSTATE VALUE '99999'
   --   SET MESSAGE_TEXT = 'Prerequisite schema version not found ' ;
END IF; 

END|

DELIMITER ;

CALL updateSymbiotaSchema();
