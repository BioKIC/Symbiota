<?php
/*
------------------
Language: Français (French)
Translated by: Google Translate (2024-09-13)
------------------
*/

$LANG['OCC_EXP_MAN'] = 'Gestionnaire d\'Exportation d\'Occurrences';
$LANG['NEW_RECORDS_PROC_STATUS'] = 'Les nouveaux enregistrements ne peuvent pas avoir un statut de traitement non traité ou indéfini. Veuillez sélectionner un statut de traitement valide.';
$LANG['EXP_TYPE'] = 'Type d\'Exportation';
$LANG['CUSTOM_EXP'] = 'Exportation Personnalisée';
$LANG['GEO_EXP'] = 'Exportation de Géoréférence';
$LANG['EXPORT_EXPLAIN'] = 'Ce module de téléchargement est conçu pour aider les gestionnaires de collections à extraire les données sur les spécimens.					pour importation dans les systèmes locaux de gestion ou de recherche.';
$LANG['MORE'] = 'plus d\'infos';
$LANG['EXPORT_EXPLAIN_2'] = 'Le module d\'exportation est particulièrement utile pour extraire les données qui ont été ajoutées					en utilisant les outils de numérisation intégrés au portail web (crowdsourcing, OCR/NLP, saisie de données de base, etc.).					Les enregistrements importés d\'une base de données locale sont liés à l\'enregistrement principal					via un spécimen d\'identifiant unique (code-barres, clé primaire, UUID, etc.).					Cet identifiant est stocké dans la base de données du portail Web et donne aux gestionnaires de collections la possibilité de mettre à jour les enregistrements locaux.					avec des informations ajoutées dans le portail Web.					Les nouveaux enregistrements numérisés directement dans le portail Web (par exemple, une image pour enregistrer le flux de travail de saisie de données) auront un identifiant unique nul,					qui identifie l\'enregistrement comme nouveau et non encore synchronisé avec la base de données centrale.					Lorsque de nouveaux enregistrements sont extraits du portail, importés dans la base de données centrale,					puis l\'instantané des données du portail est actualisé, le numéro de catalogue sera utilisé pour être automatiquement synchronisé					les enregistrements des spécimens du portail avec ceux de la base de données centrale. Notez que la synchronisation ne fonctionnera que si l\'identifiant principal est					appliqué comme unique (par exemple, pas de doublons) dans la base de données locale et centrale.';
$LANG['EXPORT_BATCH_GEO'] = 'Exporter Données Géoréférencées Par Lots';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_1'] = 'Ce module extrait les données de coordonnées uniquement pour les enregistrements géoréférencés à l\'aide du';
$LANG['BATCH_GEO_TOOLS'] = 'outils de géoréférencement par lots';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_2'] = 'ou les outils de la communauté GeoLocate.					Ces téléchargements sont particulièrement adaptés pour importer les nouvelles coordonnées dans leur base de données locale.					Si aucun enregistrement n\'a été géoréférencé dans le portail, le fichier de sortie sera vide.';
$LANG['PROCESSING_STATUS'] = 'Statut de Traitement';
$LANG['ALL_RECORDS'] = 'Tous Enregistrements';
$LANG['COMPRESSION'] = 'Compression';
$LANG['ARCHIVE_DATA_PACK'] = 'Paquet de Données d\'Archive (Fichier ZIP)';
$LANG['FILE_FORMAT'] = 'Format de Fichier';
$LANG['CSV'] = 'Délimité par des Virgules (CSV)';
$LANG['TAB_DELIMITED'] = 'Onglet Délimité';
$LANG['CHAR_SET'] = 'Jeu de Caractères';
$LANG['EXPORT_LACKING_GEO'] = 'Exporter Spécimens Manquant de Données de Géoréférencement';
$LANG['EXPORT_LACKING_GEO_EXPLAIN'] = 'Ce module extrait les spécimens dépourvus de coordonnées décimales ou dont les coordonnées doivent être vérifiées.					Ce téléchargement générera une archive Darwin Core contenant un fichier CSV encodé en UTF-8 contenant					géoréférencer uniquement les colonnes de données pertinentes pour les occurrences. Par défaut, les occurrences					sera limité aux enregistrements contenant des informations sur la localité mais pas de coordonnées décimales.					Cette sortie est particulièrement utile pour créer des extraits de données qui seront géoréférencés à l\'aide d\'outils externes.';
$LANG['COORDINATES'] = 'Coordonnées';
$LANG['ARE_EMPTY'] = 'sont vides (est nul)';
$LANG['HAVE_VALUES'] = 'avoir des valeurs (par exemple, besoin vérification)';
$LANG['ADDITIONAL_FILTERS'] = 'Filtres<br/>Supplémentaires';
$LANG['SELECT_FIELD'] = 'Sélectionnez Champ';
$LANG['DOWNLOAD_RECORDS'] = 'Télécharger Enregistrements';
$LANG['DOWNLOAD_SPEC_RECORDS'] = 'Télécharger Enregistrements de Spécimens';
$LANG['NEW_RECORDS_ONLY'] = 'Nouveaux Enregistrements Uniquement';
$LANG['EG_IN_PORTAL'] = '(par exemple, enregistrements traités dans le portail)';
$LANG['MORE_INFO'] = 'Plus d\'Information';
$LANG['MORE_INFO_TEXT'] = 'Limité aux nouveaux enregistrements saisis et traités directement dans le					portail qui n\'a pas encore été importé et synchronisé avec					la base de données centrale. Évitez d\'importer des enregistrements squelettiques non traités, car					les importations futures impliqueront une coordination des données plus complexe.';
$LANG['TRAIT_FILTER'] = 'Caractère d\'Occurrence<br/>Filtre';
$LANG['OR_SPEC_ATTRIBUTE'] = 'OU sélectionnez un état d\'Attribut spécifique';
$LANG['HOLD_CTRL'] = 'Maintenez enfoncé le bouton de contrôle (ctrl) ou de commande pour sélectionner plusieurs options';
$LANG['STRUCTURE'] = 'Structure';
$LANG['SYMB_NATIVE'] = 'Symbiota native';
$LANG['SYMB_NATIVE_EXPLAIN'] = 'Symbiota native est très similaire à Darwin Core sauf avec l\'ajout de quelques champs					tels que le substrat, les collecteurs associés, la description textuelle.';
$LANG['DWC_EXPLAIN'] = 'Darwin Core est une norme d\'échange approuvée par le TDWG spécifiquement pour les ensembles de données sur la biodiversité.					Pour plus d\'informations, visitez le site Web <a href=';
$LANG['DATA_EXTENSIONS'] = 'Extensions de Données';
$LANG['INCLUDE_DET'] = 'inclure l\'Historique des Déterminations';
$LANG['INCLUDE_IMAGES'] = 'inclure les enregistrements multimédias';
$LANG['INCLUDE_ATTRIBUTES'] = 'inclure Attributs de Trait d\'Occurrence (extension MeasurementOrFact)';
$LANG['OUTPUT_COMPRESSED'] = 'Sortie doit être une archive compressée';
$LANG['ACCESS_DENIED'] = 'Accès refusé';
$LANG['INCLUDE_ASSOCIATIONS'] = 'inclure les Relations entre Ressources (associations et ressources liées)';

?>