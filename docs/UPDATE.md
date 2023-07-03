# Updating Symbiota

## Code Updates

If you installed Symbiota through a GitHub code repository using the clone command (git clone https://github.com/BioKIC/Symbiota.git), code changes and bugs fixes can be integrated simply by running "git pull" via command line. After updating the code, make sure your database schema matches your code version (see section below).  

## Database Schema Updates

Some PHP code updates will require database schema modifications. Schema changes can be applied by running new schema patches added since the last update (MySQL command line: `source db_schema_patch_3.x.sql` or running the appropriate file through a database client). 

Current code and database schema version numbers are listed at the bottom of `sitemap.php` page. Make sure the code and database schema version match. Otherwise, run the database schema patch scripts in the correct order until they match. For example, run `db_schema_patch_1.1.sql`, and then `db_schema_patch_1.2.sql`, and finally `db_schema_patch_3.0.sql` to bring the database schema version to 3.0.
