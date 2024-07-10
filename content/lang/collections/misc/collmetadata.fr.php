<?php
/*
------------------
Language: Français (French)
------------------
*/

include_once($SERVER_ROOT.'/content/lang/collections/misc/sharedterms.fr.php');

$LANG['ADD_SUCCESS'] = 'Nouvelle collection ajoutée avec succès';
$LANG['ADD_STUFF'] = 'Ajoutez des contacts, des liens vers des ressources ou l\'adresse de l\'institution ci-dessous';
$LANG['COL_PROFS'] = 'Profils de Collecte';
$LANG['LOADING'] = '<p>En train de charger</p>';
$LANG['META_EDIT'] = 'Éditeur de Métadonnées';
$LANG['CREATE_COLL'] = 'Créer Nouveau Profil de Collection';
$LANG['COL_META_EDIT'] = 'Éditeur de Métadonnées de Collection';
$LANG['CONT_RES'] = 'Contacts et Ressources';
$LANG['COL_INFO'] = 'Informations sur Collecte';
$LANG['INST_CODE'] = 'Code de l\'Établissement';
$LANG['MORE_INST_CODE'] = 'Plus d\'Informations sur Code de Établissement';
$LANG['NAME_ONE'] = "Le nom (ou l'acronyme) utilisé par l'institution ayant la garde des dossiers d'événement. Ce champ est requis. Pour plus de détails, voir";
$LANG['DWC_DEF'] = 'Définition de Darwin Core';
$LANG['COLL_CODE'] = 'Code de Collecte';
$LANG['MORE_COLL_CODE'] = "Plus d'Informations sur Code de Collecte";
$LANG['NAME_ACRO'] = "Le nom, l'acronyme ou le code identifiant la collection ou l'ensemble de données d'où provient l'enregistrement. Ce champ est facultatif. Pour plus de détails, voir";
$LANG['COLL_NAME'] = 'Nom de Collection';
$LANG['DESC'] = 'Description (2000 caractères maximum)';
$LANG['HOMEPAGE'] = 'Page d\'Accueil';
$LANG['CONTACT'] = 'Contact';
$LANG['EMAIL'] = 'Email';
$LANG['LAT'] = 'Latitude';
$LANG['LONG'] = 'Longitude';
$LANG['MAP'] = 'Ouvrir la carte';
$LANG['MORE_INFO'] = 'Afficher plus d\'informations';
$LANG['CATEGORY'] = 'Catégorie';
$LANG['NO_CATEGORY'] = 'Aucune Catégorie';
$LANG['ALLOW_PUBLIC_EDITS'] = 'Autoriser Modifications Publiques';
$LANG['MORE_PUB_EDITS'] = "Plus d'informations sur Modifications Publiques";
$LANG['EXPLAIN_PUBLIC'] = "La vérification des modifications publiques permettra à tout utilisateur connecté au système de modifier les enregistrements de spécimens
					et de résoudre les erreurs trouvées dans la collection.
					Cependant, si l'utilisateur n'a pas d'autorisation explicite pour la collection donnée,
					les modifications ne seront pas appliquées tant qu'elles n'auront pas été examinées et approuvées par l'administrateur de la collection.";
$LANG['LICENSE'] = 'Licence';
$LANG['MORE_INFO_RIGHTS'] = "Plus d'informations sur Droits";
$LANG['ORPHANED'] = 'terme orphelin';
$LANG['LEGAL_DOC'] = "Un document légal donnant l'autorisation officielle de faire quelque chose avec la ressource.
					Ce champ peut être limité à un ensemble de valeurs en modifiant le fichier de configuration central du portail.
					Pour plus de détails, voir";
$LANG['RIGHTS_HOLDER'] = 'Titulaire des Droits';
$LANG['MORE_INFO_RIGHTS_H'] = "Plus d'informations sur Titulaire des Droits";
$LANG['HOLDER_DEF'] = "L'organisation ou la personne qui gère ou détient les droits de la ressource.
					Pour plus de détails, voir";
$LANG['ACCESS_RIGHTS'] = "Droits d'Accès";
$LANG['MORE_INFO_ACCESS_RIGHTS'] = "Plus d'informations sur Droits d'Accès";
$LANG['ACCESS_DEF'] = 'Des informations ou un lien URL vers une page avec des détails expliquant comment utiliser les données. Voir';
$LANG['DATASET_TYPE'] = 'Type de Jeu de Données';
$LANG['PRES_SPECS'] = 'Spécimens Conservés';
$LANG['OBSERVATIONS'] = 'Observations';
$LANG['PERS_OBS_MAN'] = 'Gestion des Observations Personnelles';
$LANG['MORE_COL_TYPE'] = "Plus d'informations sur Type de Collection";
$LANG['COL_TYPE_DEF'] = "Spécimens conservés désignent un type de collection qui contient des échantillons physiques disponibles
						pour inspection par les chercheurs et les experts taxonomiques. Utilisez Observations lorsque l'enregistrement n'est pas basé sur un spécimen physique.
						Gestion des Observations Personnelles est un ensemble de données où les utilisateurs enregistrés peuvent gérer indépendamment leur propre sous-ensemble d'enregistrements.
						Les enregistrements saisis dans cet ensemble de données sont explicitement liés au profil de l'utilisateur et ne peuvent être modifiés que par lui.
						Ce type de collecte est généralement utilisé par les chercheurs de terrain pour gérer leurs données de collecte et imprimer des étiquettes
						avant de déposer le matériel physique dans une collection. Même si les collections personnelles
						sont représentés par un échantillon physique, ils sont classés en &quot;observations jusqu'à ce que le
						le matériel physique est accessible au public au sein d'une collection.";
$LANG['MANAGEMENT'] = 'Gestion';
$LANG['SNAPSHOT'] = 'Instantané';
$LANG['LIVE_DATA'] = 'Données en Direct';
$LANG['AGGREGATE'] = 'Agrégat';
$LANG['MORE_INFO_TYPE'] = "Plus d'informations sur Type de Gestion";
$LANG['SNAPSHOT_DEF'] = "Utilisez Snapshot lorsqu'une base de données interne distincte est conservée dans la collection et que l'ensemble de données
						du portail Symbiota n'est qu'un instantané mis à jour périodiquement de la base de données centrale.
						Un Jeu de Données en Direct est lorsque les données sont gérées directement dans le portail et que la base de données centrale est constituée des données du portail.";
$LANG['GUID_SOURCE'] = 'Source de GUID';
$LANG['NOT_DEFINED'] = 'Non défini';
$LANG['MORE_INFO_GUID'] = "Plus d'informations sur Identifiant Unique Global";
$LANG['OCCURRENCE_ID'] = "ID d'Occurrence";
$LANG['SYMB_GUID'] = 'GUID (UUID) Généré par Symbiota';
$LANG['OCCID_DEF_1'] = "ID d'Occurrence est généralement utilisé pour les ensembles de données d'Instantané
						lorsqu'un champ d'identificateur unique global (GUID) est fourni par la base de données source
						(par exemple, spécifier la base de données) et que le GUID est mappé sur le";
$LANG['OCCURRENCEID'] = 'occurrenceId';
$LANG['OCCID_DEF_2'] = "champ. L'utilisation de l'ID d'Occurrence comme GUID n'est pas recommandée pour les Jeux de Données en Direct.
						Le numéro de catalogue peut être utilisé lorsque la valeur dans le champ du numéro de catalogue est globalement unique.
						L'option GUID (UUID) Généré par Symbiota déclenchera le portail de données Symbiota pour générer automatiquement
						des UUID GUID pour chaque enregistrement. Cette option est recommandée pour beaucoup pour les Jeux de Données en Direct
						mais non autorisé pour les collections d'instantanés gérées dans le système de gestion local.";
$LANG['PUBLISH_TO_AGGS'] = 'Publier sur Agrégateurs';
$LANG['MORE_INFO_AGGREGATORS'] = 'Plus d\'informations sur la publication sur des agrégateurs';
$LANG['ACTIVATE_GBIF'] = "Active les outils de publication GBIF disponibles dans l'option de menu Publier Archive Darwin Core";
$LANG['SOURCE_REC_URL'] = "URL de l'Enregistrement Source";
$LANG['DYNAMIC_LINK_REC'] = "Lien dynamique vers la page d'enregistrement individuel de la base de données source";
$LANG['MORE_INFO_SOURCE'] = "Plus d'informations sur l'URL d'Enregistrement Source";
$LANG['ADVANCE_SETTING'] = "Réglage avancé: L'ajout d'un modèle d'URL ici insérera un lien vers l'enregistrement source dans la page des détails du spécimen.
						Un titre d'URL facultatif peut être inclus avec un deux-points délimitant le titre et l'URL.
						Par exemple, &quot;Enregistrement source SEINet";
$LANG['ADVANCE_SETTING_2'] = "affichera l'ID avec l'url pointant vers l'original
						enregistrement géré au sein de SEINet. Ou";
$LANG['ADVANCE_SETTING_3'] = "peut être utilisé pour une importation iNaturalist si vous avez mappé leur champ ID en tant que qu'identifiant source
						(par exemple, dbpk) lors de l'importation. Modèles de modèles --CATALOGNUMBER-- (Numéro de Catalogue,
						--OTHERCATALOGNUMBERS-- (Autres Numéros de Catalogue), et --OCCURRENCEID-- sont des options supplémentaires.";
$LANG['ICON_URL'] = 'URL de Icône';
$LANG['WHAT_ICON'] = "Qu'est-ce qu'une icône?";
$LANG['UPLOAD_ICON'] = "Téléchargez un fichier image d'icône ou entrez l'URL d'une icône d'image qui représente la collection. Si vous saisissez l'URL d'une image déjà localisée
						sur un serveur, cliquez sur &quot;Saisir URL&quot;. Le chemin de l'URL peut être absolu ou relatif. L'utilisation d'icônes est facultative.";
$LANG['ENTER_URL'] = 'Entrer URL';
$LANG['UPLOAD_LOCAL'] = 'Télécharger Image Locale';
$LANG['SORT_SEQUENCE'] = 'Séquence de Tri';
$LANG['MORE_SORTING'] = "Plus d'informations sur Tri";
$LANG['LEAVE_IF_ALPHABET'] = 'Laissez ce champ vide si vous souhaitez que les collections soient triées par ordre alphabétique (par défaut)';
$LANG['COLLECTION_ID'] = 'Identifiant de Collection (GUID)';
$LANG['MORE_INFO'] = "Plus d'Information";
$LANG['EXPLAIN_COLLID'] = 'Identifiant unique global pour cette collection (voir';
$LANG['DWC_COLLID'] = 'dwc:collectionID';
$LANG['EXPLAIN_COLLID_2'] = "Si votre collection a déjà un GUID précédemment attribué, cet identifiant doit être représenté ici.
						Pour les spécimens physiques, la meilleure pratique recommandée est d'utiliser un identifiant d'un registre de collections tel que le
						Registre Mondial des Dépôts de Niodiversité";
$LANG['SECURITY_KEY'] = 'Clef de Sécurité';
$LANG['RECORDID'] = 'IDenregistrement';
$LANG['SAVE_EDITS'] = 'Énregistrer Modifications';
$LANG['CREATE_COLL_2'] = 'Créer Nouvelle Collection';
$LANG['TINYMCE_INFO'] = 'Descriptif des collections. Vous pouvez accéder à la barre d\'outils d\'édition de texte pour cette description en appuyant
						 sur la combinaison de touches ALT + F10 sous Windows ou OPTION + F10 sous MacOS.';

?>