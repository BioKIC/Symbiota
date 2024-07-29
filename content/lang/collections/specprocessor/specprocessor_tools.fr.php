<?php
/*
------------------
Language: Français
Date Translated: 2023-12-19
Translated by: Google Translate
------------------
*/

// duplicateharvest.php

$LANG['DUP_GEOREFERENCE'] = 'Géoréférencement en double';
$LANG['COL_MNG'] = 'Gestion des collections';
$LANG['BATCH_HARVEST_DUP'] = 'Récolte par lots à partir des doublons';
$LANG['STAGING_VARIABLES'] = 'Variables intermédiaires';
$LANG['TARGET_FIELDS'] = 'Champs cibles';
$LANG['ALL_FIELDS'] = 'Tous les champs';
$LANG['GEO_FIELDS'] = 'Champs de géoréférence';
$LANG['MATCH_METHOD'] = 'Méthode de correspondance';
$LANG['DUP_SPEC_TABLES'] = 'Tableaux de spécimens en double';
$LANG['EXS_TABLES'] = 'Tables exsiccatées';
$LANG['REC_NOT_EVAL_SINCE'] = 'Enregistrements non évalués depuis';
$LANG['PROC_STATUS'] = 'Statut du traitement';
$LANG['ALL_RECS'] = 'Tous les enregistrements';
$LANG['STAGE_1'] = 'Étape 1';
$LANG['STAGE_2'] = 'Étape 2';
$LANG['STAGE_3'] = 'Étape 3';
$LANG['UNPROCESSED'] = 'Non traité';
$LANG['BUILD_LIST'] = 'Construire la liste';
$LANG['REC_LIMIT'] = "Limite d'enregistrement";
$LANG['COLL_CODE'] = 'Collection<br/>Code';
$LANG['CAT_BR_NUM'] = 'Catalogue<br/>Numéro';
$LANG['NOT_AUTH'] = "Vous n'êtes pas autorisé à accéder à cette page";

// imageprocessor.php

$LANG['IMG_PROCESSOR'] = "Processeur d'images";
$LANG['SEL_IMPORT_TYPE'] = "Le type de mappage d'image/importation doit être sélectionné";
$LANG['USE_DEFAULT_PATH'] = "-- Utiliser Chemin par Défaut --";
$LANG['SEL_CSV'] = 'Sélectionnez un fichier CSV à télécharger';
$LANG['CSV_OR_ZIP'] = "Le fichier d'entrée doit être un fichier délimité par des virgules (CSV) ou un fichier ZIP contenant un fichier CSV";
$LANG['NEED_PATTERN_MATCH'] = 'Le terme correspondant au modèle doit avoir une valeur';
$LANG['CATNUM_IN_PARENS'] = 'La partie du catalogue du terme de correspondance de modèle doit être placée entre parenthèses';
$LANG['WEB_IMG_NUMERIC'] = "La largeur de l'image Web-six ne peut être qu'une valeur numérique";
$LANG['TN_IMG_NUMERIC'] = "La largeur de l'image miniature ne peut être qu'une valeur numérique";
$LANG['LG_IMG_NUMERIC'] = "Une grande largeur d'image ne peut être qu'une valeur numérique";
$LANG['TITLE_NOT_EMPTY'] = 'Le titre ne peut pas être vide';
$LANG['JPG_BETWEEN'] = 'La compression JPG doit être une valeur numérique comprise entre 30 et 100';
$LANG['PROC_DATE_FORMAT'] = 'La date de début du traitement doit être au format AAAA-MM-JJ (par exemple 2023-02-14)';
$LANG['CHECK_MATCH_TERM'] = 'Au moins une des cases Match Term doit être cochée';
$LANG['TARGET_MUST_UNIQUE'] = 'ERREUR : Les noms de champs cibles doivent être uniques (champ en double';
$LANG['SOURCE_MUST_UNIQUE'] = 'ERREUR : Les noms de champs source doivent être uniques (champ en double';
$LANG['MUST_MAP_CATNUM'] = "Le numéro de catalogue ou d'autres numéros de catalogue doivent être mappés à un champ d'importation";
$LANG['LARGE_URL_MAPPED'] = "L'URL des grandes images doit être mappée à un champ d'importation";
$LANG['IMG_PROCESSOR_EXPLAIN'] = 'Ces outils sont conçus pour aider les gestionnaires de collections dans le traitement par lots d\'images d\'échantillons. Contactez le gestionnaire de portail pour obtenir de l\'aide sur la configuration d\'un nouveau flux de travail.
			Une fois le profil établi, le gestionnaire de collection peut utiliser ce formulaire pour déclencher manuellement le traitement des images. Pour plus d\'informations, consultez la documentation Symbiota pour
			<b><a href="https://biokic.github.io/symbiota-docs/coll_manager/images/batch/" target="_blank">pratiques recommandées</a></b> pour l\'intégration d\'images.' ;
$LANG['IMG_FILE_UPLOAD_MAP'] = 'Carte de téléchargement de fichier image';
$LANG['SOURCE_FIELD'] = 'Champ source';
$LANG['TARGET_FIELD'] = 'Champ cible';
$LANG['SEL_TARGET_FIELD'] = 'Sélectionner le champ cible';
$LANG['CAT_NUM'] = 'Numéro de catalogue';
$LANG['OTHER_CAT_NUMS'] = 'Autres numéros de catalogue';
$LANG['LG_IMG_URL'] = 'URL des grandes images (obligatoire)';
$LANG['WEB_IMG_URL'] = 'URL de l\'image Web';
$LANG['TN_URL'] = 'URL de la miniature';
$LANG['SOURCE_URL'] = 'URL source';
$LANG['LINK_BLANK_RECORD'] = 'Lier l\'image à une nouvelle notice vierge si le numéro de catalogue n\'existe pas';
$LANG['MAP_IMGS'] = 'Images de la carte';
$LANG['SAVED_PROCESSING_PROF'] = 'Profils de traitement d\'image enregistrés';
$LANG['EDIT'] = 'Modifier';
$LANG['NEW'] = 'Nouveau';
$LANG['PROFILE'] = 'Profil';
$LANG['CLOSE_EDITOR'] = 'Fermer l\'éditeur';
$LANG['PROC_TYPE'] = 'Type de traitement';
$LANG['MAP_FROM_SERVER'] = 'Mapper des images à partir d\'un serveur local ou distant';
$LANG['URL_MAP_FILE'] = 'Fichier de mappage d\'URL d\'image';
$LANG['TITLE'] = 'Titre';
$LANG['PATT_MATCH_TERM'] = 'Terme de correspondance de modèle';
$LANG['MORE_INFO'] = 'Plus d\'informations';
$LANG['PATTERN_EXPLAIN'] = 'Expression régulière nécessaire pour extraire l\'identifiant unique du texte source.
			Par exemple, l\'expression régulière /^(WIS-L-\d{7})\D*/ extraira le numéro de catalogue WIS-L-0001234
			à partir du fichier image nommé WIS-L-0001234_a.jpg. Pour plus d\'informations sur la création d\'expressions régulières,
			recherchez sur Internet le \'Tutoriel PHP sur les expressions régulières\'';
$LANG['REPLACEMENT_TERM'] = 'Terme de remplacement';
$LANG['OPTIONAL'] = 'Facultatif';
$LANG['PATT_REPLACE_EXPLAIN'] = "Expression régulière facultative pour la correspondance sur le numéro de catalogue à remplacer par un terme de remplacement.
			Exemple 1: l'expression replace term = '/^/' combinée avec replace string = 'barcode-' convertira 0001234 => barcode-0001234.
			Exemple 2: l'expression replace term = '/XYZ-/' combinée avec une chaîne de remplacement vide convertira XYZ-0001234 => 0001234.";
$LANG['REPLACE_EXPLAIN'] = 'Chaîne de remplacement facultative à appliquer pour les correspondances de termes de remplacement d\'expression sur catalogNumber.';
$LANG['IMG_SOURCE_PATH'] = 'Chemin source de l\'image';
$LANG['IMG_TARGET_PATH'] = 'Chemin cible de l\'image';
$LANG['TARGET_PATH_EXPLAIN'] = "Chemin du serveur Web où seront déposés les dérivés d'images.
			Le serveur Web (par exemple l'utilisateur Apache) doit avoir un accès en lecture/écriture à ce répertoire.
			Si ce champ reste vide, l'image cible par défaut du portail (\$IMAGE_ROOT_PATH) sera utilisée.";
$LANG['IMG_URL_BASE'] = 'Base d\'URL des images';
$LANG['IMG_URL_EXPLAIN'] = "Préfixe de l'URL de l'image qui accédera au dossier cible depuis le navigateur.
			Ceci sera utilisé pour créer les URL des images qui seront stockées dans la base de données.
			Si l'URL absolue est fournie sans le nom de domaine, le domaine du portail sera pris en compte.
			Si ce champ reste vide, l'URL de l'image par défaut du portail sera utilisée (\$IMAGE_ROOT_URL).";
$LANG['WEB_IMG_WIDTH'] = 'Largeur de l\'image au format Web';
$LANG['WEB_IMG_EXPLAIN'] = 'Largeur de l\'image web standard en pixels. Si l\'image source est plus petite que cette largeur, le fichier sera simplement copié sans redimensionnement.';
$LANG['TN_IMG_WIDTH'] = 'Largeur de l\'image miniature';
$LANG['TN_IMG_EXPLAIN'] = 'Largeur de la vignette de l\'image en pixels. La largeur doit être supérieure à la taille de l\'image dans les pages d\'affichage des vignettes.';
$LANG['LG_IMG_WIDTH'] = 'Grande largeur d\'image';
$LANG['LG_IMG_EXPLAIN'] = "Largeur de la grande version de l'image en pixels.
			Si l'image source est plus petite que cette largeur, le fichier sera simplement copié sans redimensionnement.
			Notez que le redimensionnement de grandes images peut être limité par les paramètres de configuration PHP (par exemple, memory_limit).
			Si cela pose problème, avoir cette valeur supérieure à la largeur maximale de vos images sources évitera
			erreurs liées au rééchantillonnage de grandes images.";
$LANG['JPG_QUALITY'] = 'Qualité JPG';
$LANG['JPG_QUALITY_EXPLAIN'] = "La qualité JPG fait référence à la quantité de compression appliquée.
			La valeur doit être numérique et comprise entre 0 (pire qualité, fichier plus petit) à
			99 (meilleure qualité, fichier le plus gros). N'utilisez pas 100 ; cela augmentera par erreur la taille de votre image.
			Si nul, 75 est utilisé par défaut.";
$LANG['THUMBNAIL'] = 'Vignette';
$LANG['CREATE_NEW_TN'] = 'Créer une nouvelle vignette à partir de l\'image source';
$LANG['IMPORT_TN_SOURCE'] = 'Importer la vignette depuis l\'emplacement source (nom de la source avec le suffixe _tn.jpg)';
$LANG['MAP_TN_AT_SOURCE'] = 'Mapper vers la vignette à l\'emplacement source (nom de la source avec le suffixe _tn.jpg)';
$LANG['EXCLUDE_TN'] = 'Exclure la vignette';
$LANG['LG_IMG'] = 'Grande image';
$LANG['IMPORT_LG_SOURCE'] = 'Importer l\'image source en grande version';
$LANG['MAP_TO_LG_SOURCE'] = 'Mapper à l\'image source en version grande taille';
$LANG['IMPORT_LG_FROM_SOURCE'] = 'Importer une version volumineuse depuis l\'emplacement source (nom de la source avec le suffixe _lg.jpg)';
$LANG['MAP_LG_AT_SOURCE'] = 'Mapper vers une grande version existante (nom de la source avec suffixe _lg.jpg)';
$LANG['EXCLUDE_LG'] = 'Exclure la version volumineuse';
$LANG['SEL_URL_MAP_FILE'] = 'Sélectionner le fichier de mappage d\'URL';
$LANG['CHOOSE_FILE'] = 'Choisir un fichier';
$LANG['SAVE_PROFILE'] = 'Enregistrer le profil';
$LANG['SURE_DELETE_PROF'] = 'Êtes-vous sûr de vouloir supprimer ce profil de traitement d\'image ?';
$LANG['DELETE_PROJ'] = 'Supprimer le projet';
$LANG['DELETE_PROF'] = 'Supprimer le profil';
$LANG['SHOW_ALL_OR_ADD'] = 'Afficher tous les profils enregistrés ou en ajouter un nouveau...';
$LANG['OPEN_EDITOR'] = 'Ouvrir l\'éditeur';
$LANG['NO_RUN_DATE'] = 'pas de date d\'exécution';
$LANG['LAST_RUN_DATE'] = 'Date de la dernière exécution';
$LANG['PROC_START_DATE'] = 'Date de début du traitement';
$LANG['REPLACEMENT_STR'] = 'Chaîne de remplacement';
$LANG['SOURCE_PATH'] = 'Chemin source';
$LANG['TARGET_FOLDER'] = 'Dossier cible';
$LANG['URL_PREFIX'] = 'Préfixe d\'URL';
$LANG['WEB_IMG'] = 'Image Web';
$LANG['EVALUATE_IMPORT_SOURCE'] = 'Évaluer et importer l\'image source';
$LANG['IMPORT_WITHOUT_RESIZE'] = 'Importer l\'image source telle quelle sans la redimensionner';
$LANG['MAP_SOURCE_NO_IMPORT'] = 'Mapper à l\'image source sans importer';
$LANG['UNABLE_MATCH_ID'] = 'Impossible de faire correspondre l\'identifiant principal avec un enregistrement de base de données existant';
$LANG['MISSING_RECORD'] = 'Enregistrement manquant';
$LANG['SKIP_AND_NEXT'] = 'Ignorer l\'importation d\'images et passer à la suivante';
$LANG['CREATE_AND_LINK'] = 'Créer un enregistrement vide et lier une image';
$LANG['IMG_EXISTS'] = 'L\'image existe déjà';
$LANG['SKIP_IMPORT'] = 'Ignorer l\'importation';
$LANG['RENAME_SAVE_BOTH'] = 'Renommer l\'image et enregistrer les deux';
$LANG['REPLACE_EXISTING'] = 'Remplacer l\'image existante';
$LANG['LOOK_FOR_SKELETAL'] = 'Rechercher et traiter les fichiers squelettes (extensions autorisées : csv, txt, tab, dat)';
$LANG['SKIP_SKELETAL'] = 'Ignorer les fichiers squelettes';
$LANG['PROCESS_SKELETAL'] = 'Traiter les fichiers squelettiques';
$LANG['COLLID_NOT_DEFINED'] = 'ERREUR : identifiant de collection non défini. Contacter l\'administrateur';
$LANG['LOG_FILES'] = 'Fichiers journaux';
$LANG['GEN_PROCESSING'] = 'Traitement général';
$LANG['IPLANT'] = 'iPlant (pré-CyVerse)';
$LANG['CYVERSE'] = 'CyVerse';
$LANG['IMG_MAP_FILE'] = 'Fichier de mappage d\'images';
$LANG['NO_LOGS'] = 'Aucun journal n\'existe pour cette collection';

// nlpprocessor.php
$LANG['NLP_PROCESSOR'] = 'Processeur NLP';
$LANG['UNPROCESSED_SPECS'] = 'Spécimens non traités';
$LANG['UNPROCESSED_SPECS_NO_IMGS'] = 'Spécimens non traitésSpécimens non traités sans images';
$LANG['UNPROCESSED_SPECS_NO_OCR'] = 'Spécimens non traités sans OCR';
$LANG['NO_UNPROCESSED'] = 'Il n\'y a aucun enregistrement non traité vers';
$LANG['UNIDENTIFIED_ERROR'] = 'Erreur non identifiée';

// ocrprocessor.php
$LANG['PLS_SEL_PROC_STATUS'] = 'Veuillez sélectionner un statut de traitement';
$LANG['ENTER_PATT_MATCH'] = 'Veuillez saisir une chaîne de correspondance de modèle pour extraire le numéro de catalogue';
$LANG['SEL_OCR_INPUT'] = 'Veuillez sélectionner/saisir un fichier source d\'entrée OCR';
$LANG['UPLOAD_MUST_ZIP'] = 'Le fichier téléchargé doit être un fichier ZIP avec une extension .zip';
$LANG['SPEC_IMG_STATS'] = 'Statistiques d\'image d\'échantillon';
$LANG['TOTAL_W_IMGS'] = 'Total des spécimens avec images';
$LANG['SPEC_W_IMGS'] = 'spécimens avec images';
$LANG['W_OCR'] = 'avec OCR';
$LANG['WO_OCR'] = 'sans OCR';
$LANG['CUSTOM_QUERY'] = 'Requête personnalisée';
$LANG['SEL_PROC_STATUS'] = 'Sélectionner l\'état du traitement';
$LANG['NO_STATUS'] = 'Aucun statut';
$LANG['RESET_STATS'] = 'Réinitialiser les statistiques';
$LANG['BATCH_OCR_IMGS'] = 'Regroupez des images OCR à l\'aide du moteur OCR Tesseract';
$LANG['PROC_STATUS'] = 'Statut du traitement';
$LANG['UNPROCESSED'] = 'non traité';
$LANG['NUM_RECORDS_PROCESS'] = 'Nombre d\'enregistrements à traiter';
$LANG['RUN_BATCH_OCR'] = 'Exécuter l\'OCR par lots';
$LANG['TESSERACT_DEPEND'] = 'Remarque: Cette fonctionnalité dépend de la bonne installation du moteur OCR Tesseract sur le serveur d\'hébergement';
$LANG['NO_TESSERACT'] = 'Le moteur Tesseract OCR ne semble pas être installé ou la variable tesseractPath n\'est pas définie dans le fichier de configuration Symbiota.';
$LANG['CONTACT_SYSADMIN'] = 'Contactez votre administrateur système pour résoudre ces problèmes.';
$LANG['OCR_IMPORT_TOOL'] = 'Outil d\'importation par lots OCR';
$LANG['OCR_IMPORT_EXPLAIN'] = "Cette interface téléchargera les fichiers texte OCR générés en dehors de l'environnement du portail.
			Par exemple, ABBYY FineReader a la capacité de regrouper des images d'échantillons OCR et de générer les résultats sous forme de fichiers texte distincts (.txt) nommés d'après l'image source.
			Les fichiers texte OCR sont liés aux enregistrements de spécimens en faisant correspondre les numéros de catalogue extraits du nom de fichier et en comparant les noms de fichiers OCR et iamge.";
$LANG['REQS'] = 'Exigences';
$LANG['REQ1'] = 'Les fichiers OCR doivent être au format texte avec une extension .txt. Lorsque vous utilisez ABBYY, utilisez les paramètres: "Créer un document distinct pour chaque fichier", "Enregistrer sous forme de texte (*.txt)" et "Nom en tant que fichier source"';
$LANG['REQ2'] = 'Compressez plusieurs fichiers texte OCR en un seul fichier zip à télécharger sur le portail';
$LANG['REQ3'] = 'Les fichiers doivent être nommés en utilisant le numéro de catalogue. L\'expression régulière ci-dessous sera utilisée pour extraire le numéro de catalogue du nom de fichier. Cliquez sur le symbole d\'information pour plus d\'informations.';
$LANG['REQ4'] = 'Puisque le texte OCR doit être lié à l\'image source, les images doivent avoir été préalablement téléchargées sur le portail';
$LANG['REQ5'] = 'S\'il y a plus d\'une image liée à un spécimen, le nom complet du fichier sera utilisé pour déterminer quelle image lier l\'OCR';
$LANG['REGEX'] = 'Expression régulière';
$LANG['REGEX_EXPLAIN'] = 'Expression régulière nécessaire pour extraire l\'identifiant unique du texte source.
			Par exemple, l\'expression régulière /^(WIS-L-\d{7})\D*/ extraira le numéro de catalogue WIS-L-0001234
			à partir du fichier image nommé WIS-L-0001234_a.jpg. Pour plus d\'informations sur la création d\'expressions régulières,
			recherchez sur Internet le "Tutoriel PHP sur les expressions régulières". Il est recommandé d\'avoir le gestionnaire du portail
			aider à la configuration initiale du traitement par lots.';
$LANG['ZIP_W_OCR'] = 'Fichier zip contenant OCR';
$LANG['TOGGLE_FULL_PATH'] = 'activer/désactiver l\'option pour saisir le chemin complet';
$LANG['FULL_PATH'] = 'option de chemin complet';
$LANG['BROWSE_SEL_ZIP'] = 'Parcourir et sélectionner le fichier zip contenant les multiples fichiers texte OCR.';
$LANG['SOURCE_PATH_EXPLAIN'] = 'Chemin du fichier ou URL du dossier contenant les fichiers texte OCR.
Si une URL (par exemple http://) est fournie, le serveur Web doit être configuré pour répertorier
tous les fichiers du répertoire, ou la sortie HTML doit répertorier toutes les images dans des balises d\'ancrage.
Les scripts tenteront d\'explorer tous les répertoires enfants.';
$LANG['OCR_SOURCE'] = 'Source OCR';
$LANG['OCR_SOURCE_EXPLAIN'] = 'Chaîne courte décrivant la source OCR (par exemple ABBYY, Tesseract, etc.). Cette valeur est placée dans le champ source avec la date actuelle ajoutée.';
$LANG['LOAD_OCR_FILES'] = 'Charger les fichiers OCR';


?>
