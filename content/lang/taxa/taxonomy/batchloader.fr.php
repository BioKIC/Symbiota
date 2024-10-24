<?php
/*
------------------
Language: Français (French)
Translated by: Google Translate
Translation date: 2024-10-16
------------------
*/
$LANG['TAXA_LOADER'] = 'Taxa Loader';
$LANG['ENTER_PATH'] = "Veuillez saisir une valeur de chemin d'accès au fichier que vous souhaitez télécharger";
$LANG['UPLOAD_ZIP'] = "Le fichier téléchargé doit être un fichier CSV ou ZIP";
$LANG['SEL_KINGDOM'] = "Sélectionnez le royaume cible";
$LANG['ERROR_SOURCE_DUP'] = "ERREUR: les noms des champs sources doivent être uniques (champ en double:";
$LANG['ERROR_TARGET'] = "ERREUR: impossible de mapper le même champ cible plus d'une fois";
$LANG['ENTER_TAX_NODE'] = 'Veuillez saisir un nœud taxonomique valide';
$LANG['SEL_THESAURUS'] = 'Veuillez sélectionner le thésaurus taxonomique cible';
$LANG['PLS_SEL_KINGDOM'] = 'Veuillez sélectionner le royaume cible';
$LANG['SEL_AUTHORITY'] = 'Veuillez sélectionner une autorité taxonomique qui sera utilisée pour la récolte';
$LANG['HOME'] = 'Accueil';
$LANG['BASIC_TREE_VIEWER'] = 'Arbre de base Viewer';
$LANG['DYN_TREE_VIEWER'] = 'Dynamic Tree Viewer';
$LANG['TAX_BATCH_LOADER'] = 'Taxa Batch Loader';
$LANG['TAX_NAME_BATCH_LOADER'] = 'Taxonomic Name Batch Loader';
$LANG['TAX_UPLOAD_EXPLAIN1'] = 'Cette page permet à un administrateur taxonomique de télécharger par lots des fichiers de données taxonomiques. Voir';
$LANG['SYMB_DOC'] = 'Documentation Symbiota';
$LANG['TAX_UPLOAD_EXPLAIN2'] = 'pages pour plus de détails sur la mise en page du thésaurus taxonomique.';
$LANG['TAX_UPLOAD'] = 'Taxa Upload';
$LANG['SOURCE_FIELD'] = 'Champ source';
$LANG['TARGET_FIELD'] = 'Champ cible';
$LANG['FIELD_UNMAPPED'] = 'Champ non mappé';
$LANG['LEAVE_UNMAPPED'] = 'Laisser le champ non mappé';
$LANG['YELLOW_FIELDS'] = "Les champs en jaune n'ont pas encore été vérifiés";
$LANG['TARGET_KINGDOM'] = 'Royaume cible';
$LANG['TARGET_THESAURUS'] = 'Thésaurus cible';
$LANG['VERIFY_MAPPING'] = 'Vérifier le mappage';
$LANG['UPLOAD_TAXA'] = 'Télécharger les taxons';
$LANG['TRANSFER_TO_CENTRAL'] = 'Transférer les taxons vers la table centrale';
$LANG['REVIEW_BEFORE_ACTIVATE'] = "Consultez les statistiques de téléchargement ci-dessous avant d'activer. Utilisez l'option de téléchargement pour consulter et/ou ajuster le rechargement si nécessaire.";
$LANG['TAXA_UPLOADED'] = 'Taxons téléchargés';
$LANG['TOTAL_TAXA'] = 'Taxons totaux';
$LANG['INCLUDES_PARENTS'] = 'inclut les nouveaux taxons parents';
$LANG['TAXA_IN_THES'] = 'Taxons déjà dans le thésaurus';
$LANG['NEW_TAXA'] = 'Nouveaux taxons';
$LANG['ACCEPTED_TAXA'] = 'Taxons acceptés';
$LANG['NON_ACCEPTED_TAXA'] = 'Taxons non acceptés';
$LANG['PROBLEM_TAXA'] = 'Taxons problématiques';
$LANG['TAXA_FAILED'] = "Ces taxons sont marqués comme ÉCHEC et ne se chargeront pas tant que les problèmes n'auront pas été résolus. Vous pouvez télécharger les données (lien ci-dessous), corriger les mauvaises relations, puis recharger.";
$LANG['STATS_NOT_AVAIL'] = 'Les statistiques de téléchargement ne sont pas disponibles';
$LANG['ACTIVATE_TAXA'] = 'Activer les taxons';
$LANG['DOWNLOAD_CSV'] = 'Télécharger le fichier CSV des taxons';
$LANG['TAX_UPLOAD_SUCCESS'] = 'Le téléchargement des taxons semble avoir réussi';
$LANG['GO_TO'] = 'Aller à';
$LANG['TAX_TREE_SEARCH'] = "Recherche d'arbre taxonomique";
$LANG['TO_QUERY'] = 'page à interroger pour un nom chargé';
$LANG['ACTION_PANEL'] = "Panneau d'action";
$LANG['RESULT_TARGETS'] = 'Cibles de résultat';
$LANG['TARGET_TAXON'] = 'Taxon cible';
$LANG['KINGDOM'] = 'Royaume';
$LANG['LOWEST_RANK'] = 'Limite de rang la plus basse';
$LANG['SOURCE_LINK'] = 'Lien source';
$LANG['TOTAL_RESULTS'] = 'Total des résultats';
$LANG['ID'] = 'ID';
$LANG['ERROR'] = 'ERREUR';
$LANG['NAME'] = 'Nom';
$LANG['DATSET_KEY'] = 'Clé du jeu de données';
$LANG['STATUS'] = 'Statut';
$LANG['ACC_TO'] = 'Selon';
$LANG['SCRUTINIZER'] = 'Scrutateur';
$LANG['NOT_PREF'] = 'non préféré';
$LANG['PREF_TARGET'] = 'cible préférée';
$LANG['TARGET_STATUS'] = 'Statut de la cible';
$LANG['WEB_SERVICE_URL'] = 'URL du service Web';
$LANG['API_URL'] = "URL de l'API";
$LANG['COL_URL'] = 'URL CoL';
$LANG['IMPORT_THIS_NODE'] = 'Importer ce nœud et les taxons enfants';
$LANG['NO_VALID_COL'] = "ERREUR: aucune cible CoL valide n'a été renvoyée";
$LANG['TAXA_LOADED_SUCCESS'] = 'Les taxons du nœud cible ont été chargés avec succès';
$LANG['TAX_UPLOAD_INSTRUCTIONS'] = "Des fichiers texte CSV (délimités par des virgules) à structure plate peuvent être téléchargés ici.
			Le nom scientifique est le seul champ obligatoire sous le rang de genre.
			Cependant, la famille, l'auteur et le rankid (tels que définis dans le tableau des taxonunits) sont toujours conseillés.
			Pour les taxons de niveau supérieur, les parents et les rankids doivent être inclus afin de construire la hiérarchie taxonomique.
			Les fichiers de données volumineux peuvent être compressés sous forme de fichier ZIP avant l'importation.
			Si l'étape de téléchargement du fichier échoue sans afficher de message d'erreur, il est possible que la
			taille du fichier dépasse les limites de téléchargement de fichier définies dans votre installation PHP (voir votre fichier de configuration PHP).";
$LANG['FULL_FILE_PATH'] = "Chemin d'accès complet au fichier";
$LANG['FULL_FILE_EXPLAIN'] = "Cette option permet de télécharger manuellement un fichier de données. Entrez le chemin d'accès complet au fichier de données situé sur le serveur de travail.";
$LANG['MAP_INPUT_FILE'] = "Mappez le fichier d'entrée";
$LANG['TOGGLE_MANUAL'] = "Activer ou désactiver l'option de téléchargement manuel";
$LANG['CLEAN_ANALYZE'] = 'Nettoyer et analyser';
$LANG['CLEAN_ANALYZE_EXPLAIN'] = "Si les informations sur les taxons ont été chargées dans la table UploadTaxa par d'autres moyens, on peut utiliser ce formulaire pour nettoyer et analyser les noms de taxons en vue du chargement dans les tables taxonomiques (taxons, statut fiscal).";
$LANG['API_NODE_LOADER'] = 'Chargeur de nœuds API';
$LANG['API_NODE_LOADER_EXPLAIN'] = "Ce formulaire chargera par lots un nœud taxonomique à partir d'une autorité taxonomique sélectionnée via ses ressources API.<br/>
			Cette fonction ne fonctionne actuellement que pour Catalog of Life et WoRMS.";
$LANG['TAX_RESOURCE'] = 'Ressource taxonomique';
$LANG['TARGET_NODE'] = 'Nœud cible';
$LANG['ANALYZE_TAXA'] = 'Analyser les taxons';
$LANG['TAX_THESAURUS'] = 'Thésaurus taxonomique';
$LANG['ALL_RANKS'] = 'Tous les rangs taxonomiques';
$LANG['LOAD_NODE'] = 'Charger le nœud';
$LANG['NO_PERMISSIONS'] = "Vous n'avez pas les autorisations pour télécharger des données taxonomiques par lots";

?>