<?php
/*
------------------
Language: Français (French)
------------------
*/

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/occurrencetabledisplay.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrencetabledisplay.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrencetabledisplay.en.php');

$LANG['GEOREF_TOOLS'] = 'Outils de Géoréférencement';
$LANG['COL_MAN_MENU'] = 'Gestion des Collections';
$LANG['SPEC_MANAGEMENT'] = 'Gestion des Spécimens';
$LANG['BATCH_GEO_TOOLS'] = 'Outils de Géoréférencement Lar Lots';
$LANG['MULT_COL_SELECT'] = 'Sélecteur de Collections Multiples';
$LANG['SEL_DESEL_ALL'] = 'Sélectionner / Désélectionner Tout';
$LANG['EVAL_COLLS'] = 'Évaluer Collections';
$LANG['ONLY_ADMIN_COLS'] = 'Seules collections avec accès administrateur sont affichées';
$LANG['QUERY_FORM'] = 'Formulaire de Requête';
$LANG['ALL_COUNTRIES'] = 'Tous Pays';
$LANG['ALL_STATES'] = 'Tous États';
$LANG['ALL_COUNTIES'] = 'Tous Comtés';
$LANG['ALL_MUNS'] = 'Toutes Municipalités';
$LANG['ALL_PROC_STAT'] = 'Tous Statuts de Traitement';
$LANG['ADVANCED_OPT'] = 'Options Avancées';
$LANG['VERIF_STATUS'] = 'Statut de Vérification';
$LANG['FAMILY_GENUS'] = 'Famille/Genre';
$LANG['INCLUDE_PREV_GEOREF'] = 'Y compris enregistrements précédemment géoréférencés';
$LANG['GENERATE_LIST'] = 'Générer Liste';
$LANG['LOCALITY_TERM'] = 'Terme de localité';
$LANG['SEARCH_CLONES'] = 'Recherche de clones préalablement géoréférencés';
$LANG['GEOLOCATE_LOCALITY'] = 'Géolocaliser Localité';
$LANG['ANALYZE_FOR_COORDS'] = 'Analyser chaîne de localité pour lat/long ou UTM intégré';
$LANG['EDIT_FIRST_SET'] = "Modifier le premier ensemble d'enregistrements";
$LANG['LIMIT_REACHED'] = 'limite atteinte (toutes les localités disponibles ne sont pas affichées)';
$LANG['RETURN_COUNT'] = 'Nombre de Retours';
$LANG['NO_LOCALITIES_RETURNED'] = "Aucune localité n'a renvoyé terme de recherche correspondant";
$LANG['USE_QUERY_FORM]'] = 'Utilisez le formulaire de requête ci-dessus pour créer une liste de localités';
$LANG['STATISTICS'] = 'Statistiques';
$LANG['RECS_TO_GEOREF'] = 'Enregistrements à Géoréférencés';
$LANG['TOTAL'] = 'Total';
$LANG['PERCENT'] = 'Pourcentage';
$LANG['DEG'] = 'Deg'; //as in degrees
$LANG['MIN'] = 'Min'; //as in minutes
$LANG['SEC'] = 'Sec'; //as in seconds
$LANG['DECIMAL'] = 'Décimal';
$LANG['LATITUDE'] = 'Latitude';
$LANG['N'] = 'N'; //as in north
$LANG['S'] = 'S'; //as in south
$LANG['LONGITUDE'] = 'Longitude';
$LANG['E'] = 'E'; //as in east
$LANG['W'] = 'O'; //as in west
$LANG['ERROR_METERS'] = 'Erreur (en mètres)';
$LANG['DATUM'] = 'Plan de Référence';
$LANG['FOOTPRINT_WKT'] = 'Empreinte WKT';
$LANG['EAST'] = 'Est';
$LANG['NORTH'] = 'Nord';
$LANG['ZONE'] = 'Zone';
$LANG['HEMISPHERE'] = 'Hémisphère';
$LANG['SOUTH'] = 'Sud';
$LANG['CONVERT_UTMS'] = 'Convertir valeurs UTM en lat/long';
$LANG['SOURCES'] = 'Sources';
$LANG['PROTOCOLS'] = 'Protocoles';
$LANG['REMARKS'] = 'Remarques';
$LANG['ELEVATION'] = 'Élévation';
$LANG['TO'] = 'à';
$LANG['METERS'] = 'mètres';
$LANG['FEET'] = 'pieds';
$LANG['PROCESSING_STATUS'] = 'Statut de Traitement';
$LANG['LEAVE_AS_IS'] = 'Laissez tel Quel';
$LANG['GEOREF_BY'] = 'Géoréférer par';
$LANG['UPDATE_COORDS'] = 'Mettre à Jour Coordonnées';
$LANG['NOTE_EXISTING_GEOREFS'] = "Remarque : Les données de terrain de géoréférencement existantes seront remplacées par les données entrantes. 
								Cependant, les données d'altitude ne seront ajoutées que lorsque les champs cibles sont nuls.
								Les champs de géoréférence qui seront remplacés incluent: decimalLatitude, decimalLongitude, coordinateUncertaintyInMeters, geodeticdatum,
								footprintwkt, georeferencedby, georeferenceRemarks, georeferenceSources, georeferenceProtocol, georeferenceVerificationStatus";
$LANG['ERROR_NO_PERMISSIONS'] = "ERREUR : Vous n'êtes pas autorisé à modifier cette collection";
$LANG['BATCH_GEO_TOOL'] = 'Outil de Géoréférencement Par Lots';
$LANG['COL_SELECTOR'] = 'Sélecteur de Collection';
$LANG['ERROR_COL_ID_NULL'] = "ERREUR : Identifiant de collection est nul";

$LANG['TABLE_CONTENT'] = 'Formulaire de mise à jour des données géographiques : Ce formulaire facilite la modification des coordonnées, notamment la latitude, la longitude et des paramètres supplémentaires.';
$LANG['MULT_COLL_DIV'] = 'Divisions de collections multiples';
$LANG['LOCALLIST'] = 'Liste locale';
$LANG['LATITUDE_DEGREE'] = 'Degrés de latitude';
$LANG['LATITUDE_MINUTES'] = 'Minutes de latitude';
$LANG['LATITUDE_SECONDS'] = 'Secondes de latitude';
$LANG['LATITUDE_NORTH_SOUTH'] = 'Latitude Nord/Sud';
$LANG['DECIMAL_LATITUDE'] = 'Latitude décimale';
$LANG['LONGITUDE_DEGREE'] ='Degrés de longitude';
$LANG['LONGITUDE_MINUTES'] = 'Longitude Minutes';
$LANG['LONGITUDE_SECONDS'] = 'Longitude Secondes';
$LANG['LONGITUDE_EAST_WEST'] = 'Longitude Est/Ouest';
$LANG['DECIMAL_LONGITUDE'] = 'Longitude décimale';
$LANG['MINIMUM_ELEVATION_IN_METERS'] = 'Altitude minimale en mètres';
$LANG['MAXIMUM_ELEVATION_IN_METERS'] = 'Altitude maximale en mètres';
$LANG['MINIMUM_ELEVATION'] = 'Altitude minimale';
$LANG['MAXIMUM_ELEVATION'] = 'Altitude maximale';
$LANG['ADD_ICON'] = 'Ajouter';
$LANG['WORLD_ICON'] = 'Icône du monde';
$LANG['EDIT_PLUS_ICON'] = 'Modifier l\'icône Plus';
$LANG['FIND_CLONES'] ='Rechercher les doublons précédemment géolocalisés';
$LANG['LOCATE_GEO_AREA'] = 'Localiser la zone géographique';
$LANG['EXAMINE_FOR_COORDS'] = 'Examiner la localité pour Lat/Long ou UTM.';
$LANG['INITIAL_RECORDS_EDIT'] = 'Réviser le lot initial d\'enregistrements';
$LANG['SKIP_NAV'] = 'Passer la section de navigation';
?>