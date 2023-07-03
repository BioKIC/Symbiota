#SELECT COUNT(*) FROM omoccurrences;
#SELECT COUNT(*) FROM omoccurrencesfulltext;
TRUNCATE TABLE omoccurrencesfulltext;
INSERT INTO omoccurrencesfulltext(occid,locality,recordedby)
  SELECT occid, locality, recordedby FROM omoccurrences ;


#SELECT COUNT(*) FROM omoccurrences WHERE decimallatitude IS NOT NULL and decimallongitude IS NOT NULL;
#SELECT COUNT(*) FROM omoccurpoints;
TRUNCATE TABLE omoccurpoints;
INSERT INTO omoccurpoints(occid,point)
  SELECT occid, Point(decimalLatitude, decimalLongitude) FROM omoccurrences WHERE decimallatitude IS NOT NULL and decimallongitude IS NOT NULL;


#SELECT COUNT(*) FROM specprocessorrawlabels;
#SELECT COUNT(*) FROM specprocessorrawlabelsfulltext;
TRUNCATE TABLE specprocessorrawlabelsfulltext;
INSERT INTO specprocessorrawlabelsfulltext(prlid,imgid,rawstr)
  SELECT prlid, imgid, rawstr FROM specprocessorrawlabels;