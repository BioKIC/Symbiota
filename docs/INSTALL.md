# Installing Symbiota

## REQUIREMENTS

- Apache HTTP Server (2.x or better) - other PHP enabled web servers will work, though the code has been well tested using Apache HTTP Server and Nginx
- PHP (7.3 or better) configured to your web server
  - Required extensions: mysqli, gd, mbstring, zip, curl, exif, openssl
  - Recommended configuration adjustments: upload_max_filesize = 100M (or expected file size upload), max_input_vars = 2000, memory_limit = 256M, post_max_size = 100M
  - Optional: Pear package Image_Barcode2 (https://pear.php.net/package/Image_Barcode2) – enables barcodes on specimen labels
  - Optional: Install Pear Mail for SMTP mail support: https://pear.php.net/package/Mail/redirected
- MariaDB (v10.3+) or MySQL (v5.7+)
- GIT Client - not required, though recommend for installation and updating source code

## INSTRUCTIONS

1. Download Symbiota code from GitHub repository
   - (https://github.com/BioKIC/Symbiota)
   - Command line checkout (recommended): git clone https://github.com/BioKIC/Symbiota.git
2. Install Symbiota database schema
   1. Create new database (e.g. CREATE SCHEMA symbdb CHARACTER SET utf8 COLLATE utf8_general_ci)
   2. Create read-only and read/write users for Symbiota database
      - CREATE USER 'symbreader'@'localhost' IDENTIFIED BY 'password1';
      - CREATE USER 'symbwriter'@'localhost' IDENTIFIED BY 'password2';
      - GRANT SELECT,EXECUTE ON `symbdb`.\* TO `symbreader`@localhost;
      - GRANT SELECT,UPDATE,INSERT,DELETE,EXECUTE ON `symbdb`.\* TO `symbwriter`@localhost;
   3. Load base database schema from scripts: <SymbiotaBaseFolder>/config/schema/3.0/db_schema-3.0.sql 
      - Run db_schema-3.0.sql to install the core table structure
      - From MySQL commandline: SOURCE <BaseFolderPath>/config/schema/3.0/db_schema-3.0.sql
   4. If needed, run database patch scripts to bring database up to current structure 
      - Make sure to run the scripts in the correct order e.g. db_schema_patch-3.1.sql, db_schema_patch-3.2.sql, etc.
      - From MySQL commandline: SOURCE /BaseFolderPath/config/schema/3.0/patches/db_schema_patch-3.x.sql
3. Configure the Symbiota Portal - modify following configuration files; running /config/setup.sh will create the following required files and permissions
   1. Symbiota configuration
      - rename /config/symbini_template.php to /config/symbini.php.
      - Modify variables within to match your project environment. See Symbiota configuration help page for more information on this subject.
   2. Database connection
      - Rename /config/dbconnection_template.php to /config/dbconnection.php.
      - Modify with readonly and read/write logins, passwords, and schema names.
   3. Homepage
      - Rename /index_template.php to index.php. This is your home page to which will need introductory text, graphics, etc.
   4. Layout - Within the /includes directory, rename header_template.php to header.php, and
      footer_template.php to footer.php. The header.php and footer.php files are used by all  
      pages to establish uniform layout. left_menu.php is needed if a left menu is preferred.
      - header.php: Within file, change /images/layout/defaultheader.jpg
        to /images/layout/header.jpg. Add your header to /images/layout/
        folder. Establishing the header using an image is easy, yet more
        complex header configurations are possible.
      - footer.php: modify as you did with header.php file.
   5. Files for style control - Within the /includes directory, rename head_template.php to head.php
      The head.php file is included within the <head> tag of each page.
      Thus, you can modify this file to globally change design of portal.
   6. Misc: rename usagepolicy_template.php to usagepolicy.php, and modify as needed
4. File permissions - the web server needs write access to the following files and their subdirectories  (e.g. sudo chmod -R 777 temp/)
   - /content/collections/ 
   - /content/collicon/
   - /content/dwca/
   - /content/geolocate/
   - /content/logs/
   - /temp/ 

## DATA

1. Data - The general layers of data within Symbiota are: user, taxonomic, occurrence (specimen), images,
   checklist, identification, key, and taxon profile (common names, text descriptions, etc).
   While user interfaces have been developed for web management for most of these data layers, some table tables still need to be managed via the backend (e.g. loaded by hand).
   1. User and permissions - Default administrative user has been installed with following login: username = admin; password: admin.
      It is highly recommend that you change the password, or better yet, create a new admin user, assign admin rights, and then delete default admin user.
      Management control panel for permissions is available within Data Managment Panel on the sitemap page.
   2. Taxonomic Thesaurus - Taxon names are stored within the 'taxa' table.
      Taxonomic hierarchy and placement definitions are controled in the 'taxstatus' table. 
      A recursive data relationship within the 'taxstatus' table defines the taxonomic hierarchy. 
      While multiple taxonomic thesauri can be defined, one of the thesauri needs to function as the central taxonomy. 
      Names must be added in order from upper taxonomic levels to lower (e.g. kingdom, class, order, variety). 
      Accepted names must be loaded before non-accepted names.
      1. Names can be added one-by-one using taxonomic management tools (see sitemap.php)
      2. Name can be imported from taxnomic authorities (e.g. Catalog of Life, WoRMS, TROPICOS, etc)
         based on occurrence data loaded into the system using cleaning tools
         found in Data Cleaning Tools => Analyze taxonomic names... This is recommended.
      3. Batch Loader - Multiple names can be loaded from a flat,
         tab-delimited text file. See instructions on the Batch Taxon
         Loader for detailed instructions. See instructions on the
         batch loader for loading multiple names from a flat file.
      4. Look in /config/schema/data/ folder to find taxonomic
         thesaurus data that may serve as a base for your taxonomic
         thesaurus.
   3. Occurrence (Specimen) Data: SuperAdmin can create new collection instances via
      Data Management pane within sitemap. Within the collection's data managment menu, one can  
      provide admin and read access to new users, add/edit occurrences, batch load data, etc.
   4. Images - to be completed
   5. Floristic data - to be completed
   6. Identification key data - to be completed
   7. Taxon Profile support data (common names, text descriptions, etc) - to be completed

## UPDATES

Please read the [UPDATE.md](UPDATE.md) file for instructions on how to update Symbiota.