<?php
/*
------------------
Language: Français (French)
------------------
*/

include_once($SERVER_ROOT . '/content/lang/collections/customsearchtype.fr.php');

$LANG['SPEC_UPLOAD'] = 'Télécharger Spécimens';
$LANG['PATH_EMPTY'] = 'Le chemin du fichier est vide. Veuillez sélectionner le fichier à charger.';
$LANG['MUST_CSV'] = 'Le fichier doit être séparé par des virgules (.csv), délimité par des tabulations (.txt ou .tab), un fichier ZIP (.zip) ou une URL vers une ressource IPT';
$LANG['IMPORT_FILE'] = 'Fichier à Importer ';
$LANG['IS_BIGGER'] = 'Mo) est supérieur à ce qui est autorisé (limite actuelle: ';
$LANG['MAYBE_ZIP'] = "Notez que la taille du fichier d'importation peut être réduite en compressant dans un fichier zip.";
$LANG['ERR_UNIQUE_D'] = 'ERREUR: Noms des champs source doivent être uniques (champ en double: ';
$LANG['ERR_UNIQUE_ID'] = 'ERREUR: Noms des champs source doivent être uniques (Identification: ';
$LANG['ERR_UNIQUE_IM'] = 'ERREUR: Noms des champs source doivent être uniques (Image: ';
$LANG['SAME_TARGET_D'] = 'ERREUR: Impossible de mapper plusieurs fois le même champ cible (';
$LANG['SAME_TARGET_ID'] = 'ERREUR: Impossible de mapper plusieurs fois le même champ cible (Identification: ';
$LANG['SAME_TARGET_IM'] = 'ERREUR: Impossible de mapper plusieurs fois le même champ cible (images: ';
$LANG['NEED_CAT'] = "ERREUR: cataloNumber (numéro de catalogue) ou otherCatalogNumbers (autres numéros de catalogue) est requis pour les téléchargements de fichiers squelettiques";
$LANG['SEL_MATCH'] = "ERREUR: sélectionnez l'identifiant qui sera utilisé pour la correspondance des enregistrements (requis pour les importations de fichiers squelettes)";
$LANG['ID_NOT_MATCH'] = "ERREUR: correspondance d'enregistrement d'identifiant ne correspond pas aux champs d'importation (requis pour les importations de fichier squelette)";
$LANG['SEL_TAR_USER'] = "Puisqu'il s'agit d'un projet d'observation géré par un groupe, vous devez sélectionner un utilisateur cible auquel l'occurrence sera liée";
$LANG['FIRST_ROW'] = "La première ligne du fichier d'entrée contient-elle les noms de colonnes? Il semble que vous mappiez directement à la première ligne de données actives plutôt qu'à une ligne d'en-tête. Si tel est le cas, la première ligne de données sera perdue et certaines colonnes peuvent être ignorées. Sélectionnez OK pour continuer ou annuler pour abandonner";
$LANG['ENTER_PROF'] = 'Entrez un nom de profil et cliquez sur le bouton Enregistrer la carte pour créer un nouveau profil de téléchargement';
$LANG['COL_MGMNT'] = 'Gestion des Collections';
$LANG['LIST_UPLOAD'] = 'Liste des Profils de Téléchargement';
$LANG['UP_MODULE'] = 'Module de Téléchargement';
$LANG['CAUTION'] = 'Avertir';
$LANG['REC_REPLACE'] = 'Enregistrements correspondants seront remplacés par les enregistrements entrants';
$LANG['NOT_REC'] = 'pas enregistré';
$LANG['UP_STATUS'] = 'État du Téléchargement';
$LANG['PENDING_REPORT'] = 'Rapport de Transfert de Données en Attente';
$LANG['OCCS_TRANSFERING'] = 'Occurrences en attente de transfert';
$LANG['PREVIEW'] = 'Aperçu des 1000 premiers enregistrements';
$LANG['DOWNLOAD_RECS'] = 'Télécharger Enregistrements';
$LANG['RECORDS_UPDATED'] = 'Enregistrements à mettre à jour';
$LANG['CAUTION_REPLACE'] = 'les enregistrements entrants remplaceront les enregistrements existants';
$LANG['MISMATCHED'] = 'Enregistrements non concordants';
$LANG['NEW_RECORDS'] = 'Nouveaux enregistrements';
$LANG['FAILED_LINK'] = 'Enregistrements n&apos;ont pas pu être liés aux enregistrements de cette collection et ne seront pas importés';
$LANG['MATCHING_CATALOG'] = 'Les enregistrements correspondant au numéro de catalogue qui seront ajoutés';
$LANG['WARNING'] = 'ATTENTION';
$LANG['WARNING_DUPES'] = 'Cela se traduira par des enregistrements avec des numéros de catalogue en double';
$LANG['RECS_SYNC'] = 'Enregistrements qui seront synchronisés avec la base de données centrale';
$LANG['EXPL_SYNC'] = 'Ce sont généralement des notices qui ont été initialement traitées dans le portail, exportées et intégrées dans une base de données de gestion locale, puis réimportées et synchronisées avec les notices du portail en faisant correspondre le numéro de catalogue';
$LANG['WARNING_REPLACE'] = 'Les notices entrantes remplaceront les notices du portail en faisant correspondre les numéros de catalogue. Assurez-vous que les enregistrements entrants sont les plus à jour!';
$LANG['NOT_MATCHING'] = 'Les enregistrements chargés précédemment ne correspondent pas aux enregistrements entrants';
$LANG['EXPECTED'] = 'Remarque: Si vous effectuez un téléchargement partiel, cela est attendu';
$LANG['FULL_REFRESH'] = 'Si vous effectuez une actualisation complète des données, il peut s\\&apos;agir d&apos;enregistrements supprimés dans votre base de données locale mais pas dans le portail.';
$LANG['NULL_RM'] = "Enregistrements qui seront supprimés en raison de l'Identifiant Principal NULL";
$LANG['DUP_RM'] = "Enregistrements qui seront supprimés en raison de l'Identifiant Principal DUPLICATE";
$LANG['IDENT_TRANSFER'] = "Nombre Historiques d'identification";
$LANG['IMAGE_TRANSFER'] = "Nombre d'images images";
$LANG['FINAL_TRANSFER'] = "Êtes-vous sûr de vouloir transférer les enregistrements de la table temporaire vers la table d\\&apos;échantillons centrale?";
$LANG['TRANS_RECS'] = "Transférer Enregistrements vers Table Centrale d'Échantillons";
$LANG['REC_START'] = "Début de Enregistrement";
$LANG['REC_LIM'] = "Limite d'Enregistrement";
$LANG['MATCH_CAT'] = 'Correspondance sur Numéro de Catalogue';
$LANG['MATCH_ON_CAT'] = "Correspondance sur d'Autres Numéros de Catalogue";
$LANG['APPENDED'] = 'Les données de squelette entrantes seront ajoutées uniquement si le champ ciblé est vide';
$LANG['BOTH_CATS'] = "Si les deux cases sont cochées, les correspondances seront d'abord effectuées sur les numéros de catalogue et secondairement sur d'autres numéros de catalogue";
$LANG['ID_SOURCE'] = 'Identifier Source de Données';
$LANG['IPT_URL'] = 'URL de Ressource IPT';
$LANG['RESOURCE_URL'] = 'Chemin de Ressource ou URL';
$LANG['WORKAROUND'] = "Cette option permet de pointer vers un fichier de données qui a été manuellement téléchargé sur un serveur. Cette option offre une solution de contournement pour l'importation de fichiers plus volumineux que ce qui est autorisé par les limitations de téléchargement du serveur (par exemple, les limites de configuration PHP)";
$LANG['DISPLAY_OPS'] = 'Afficher Options Supplémentaires';
$LANG['AUTOMAP'] = 'Mapper Automatiquement Champs';
$LANG['ANALYZE_FILE'] = 'Analyser Fichier';
$LANG['UNPROC'] = 'Non traité';
$LANG['STAGE_1'] = 'Étape 1';
$LANG['STAGE_2'] = 'Étape 2';
$LANG['STAGE_3'] = 'Étape 3';
$LANG['PEND_REV'] = 'En Attendant Examen';
$LANG['EXP_REQ'] = 'Expert Requis';
$LANG['PEND_NFN'] = 'En Attendant Examen-NfN';
$LANG['SOURCE_ID'] = 'Identifiant Unique Source / Clé Primaire';
$LANG['REQ'] = 'requis';
$LANG['IMPORT_OCCS'] = "Importer des Enregistrements d'Occurrences";
$LANG['VIEW_DETS'] = 'voir détails';
$LANG['UNVER'] = 'Mappages non vérifiés sont affichés en jaune';
$LANG['CUSTOM_FILT'] = "Filtres d'Importation d'Enregistrements d'Occurrences Personnalisés";
$LANG['FIELD'] = 'Champ';
$LANG['SEL_FIELD'] = 'Sélectionnez Nom du Champ';
$LANG['COND'] = 'État';
$LANG['VALUE'] = 'Valeur';
$LANG['MULT_TERMS'] = 'Ajout de plusieurs termes séparés par un point-virgule filtrera comme une condition OU';
$LANG['IMPORT_ID'] = "Importer Historique d'Identification";
$LANG['UNVER'] = 'Mappages non vérifiés sont affichés en jaune';
$LANG['NOT_IN_DWC'] = 'non présent dans DwC-Archive';
$LANG['IMP_IMG'] = 'Importer Images';
$LANG['RESET_MAP'] = 'Réinitialiser Mappage des Champs';
$LANG['NEW_PROF_TITLE'] = 'Nouveau titre de profil';
$LANG['TARGET_USER'] = 'Utilisateur Cible';
$LANG['SEL_TAR_USER'] = 'Sélectionnez Utilisateur Cible';
$LANG['VER_LINKS'] = "Vérifier liens d'Images";
$LANG['PROC_STATUS'] = 'Statut de Traitement';
$LANG['NO_SETTING'] = 'Laisser Tel Quel / Pas de Paramètre Explicite';
$LANG['UNK_ERR'] = 'Erreur inconnue lors de analyse du téléchargement';
$LANG['NFN_IMPORT'] = 'Importer Fichier des Notes from Nature';
$LANG['START_UPLOAD'] = 'Commence Téléchargement';
$LANG['SEL_KEY'] = 'Sélectionnez Clé Primaire Source';
$LANG['SKIPPED'] = 'Enregistrement sera ignoré lorsque tous champs suivants sont vides: catalogNumber (Numéro de Catalogue), otherCatalogNumbers (Autres Numéros de Catalogue), occurrenceID, recordedBy (Collectionneur), eventDate (date), scientificName (Nom Scientifique), dbpk';
$LANG['LEARN_MORE'] = 'Pour en savoir plus sur la cartographie des champs Symbiota (et Darwin Core)';
$LANG['LOADING_DATA'] = 'Chargement Données dans Symbiota';
$LANG['VER_MAPPING'] = 'Vérifier Mappage';
$LANG['VERSION_DATA_CHANGES'] = 'Version data changes';
$LANG['SAVE_MAP'] = 'Enregistrer Mappage';
$LANG['VER_LINKS_MEDIA'] = "Vérifier les liens d'image à partir du champ associatedMedia (Média Associé)";
$LANG['SKEL_EXPLAIN'] = "Les fichiers squelettes sont constitués de données stub faciles à capturer en masse pendant le processus d'imagerie.
						Ces données sont utilisées pour amorcer de nouveaux enregistrements auxquels les images sont liées.
						Les champs squelettiques généralement collectés incluent classés par ou le nom scientifique actuel, le pays, l'état/la province et parfois le comté, bien que n'importe quel champ pris en charge puisse être inclus.
						Les téléchargements de fichiers squelettiques sont similaires aux téléchargements réguliers, bien qu'ils diffèrent de plusieurs manières.";
$LANG['SKEL_EXPLAIN_P1'] = 'Les téléchargements de fichiers généraux consistent généralement en des enregistrements complets, tandis que les téléchargements squelettiques seront presque toujours un enregistrement annoté avec des données pour seulement quelques champs sélectionnés';
$LANG['SKEL_EXPLAIN_P2'] = 'Le champ du numéro de catalogue est requis pour les téléchargements de fichiers squelettiques car ce champ est utilisé pour trouver des correspondances sur des images ou des enregistrements existants';
$LANG['SKEL_EXPLAIN_P3'] = "Dans les cas où un enregistrement existe déjà, un téléchargement de fichier général remplacera complètement l'enregistrement existant par les données du nouvel
	enregistrement. D'un autre côté, un téléchargement squelettique augmentera l'enregistrement existant uniquement avec de nouvelles données de terrain. Les champs ne sont ajoutés que si les données n'existent pas déjà dans le champ cible.";
$LANG['SKEL_EXPLAIN_P4'] = "Si un enregistrement N'EXISTE PAS déjà, un nouvel enregistrement sera créé dans les deux cas, mais seul l'enregistrement squelettique sera marqué comme non traité";
$LANG['NOT_AUTH'] = "ERREUR: vous n'êtes pas autorisé à télécharger dans cette collection";
$LANG['PAGE_ERROR'] = "ERREUR: Soit vous avez essayé d'accéder à cette page sans passer par le menu de gestion des collections, soit vous avez essayé de télécharger un fichier trop volumineux.
	Vous voudrez peut-être diviser le fichier de téléchargement en fichiers plus petits ou compresser le fichier dans une archive zip (extension .zip). Vous pouvez contacter
	l'administrateur du portail pour demander de l'aide pour télécharger le fichier (conseil à l'administrateur : augmenter les limites de téléchargement PHP peut aider, upload_max_filesize actuel";
$LANG['USE_BACK'] = 'Utilisez les flèches de retour pour revenir à la page de téléchargement de fichier.';
$LANG['UPLOAD'] = 'Télécharger le fichier';

?>