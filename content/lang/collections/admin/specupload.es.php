<?php
/*
------------------
Language: Español (Spanish)
------------------
*/

include_once($SERVER_ROOT . '/content/lang/collections/customsearchtype.es.php');

$LANG['SPEC_UPLOAD'] = 'Carga de Especímenes';
$LANG['PATH_EMPTY'] = 'La dirección al archivo está vacía. Por favor seleccione el archivo que va a ser cargado.';
$LANG['MUST_CSV'] = 'El archivo debe ser separado por comas (.csv), delimitado por tabulaciones (.txt o .tab), archivo ZIP (.zip), o una URL a un Recurso IPT';
$LANG['IMPORT_FILE'] = 'Importar Archivos';
$LANG['IS_BIGGER'] = 'MB) es mayor a lo permitido (límite actual: ';
$LANG['MAYBE_ZIP'] = ' Note que el tamaño del archivo de importación puede ser reducida comprimiéndolo en un archivo zip. ';
$LANG['ERR_UNIQUE_D'] = 'ERROR: Los nombres de los campos de origen deben ser únicos (campo duplicado: ';
$LANG['ERR_UNIQUE_ID'] = 'ERROR: Los nombres de los campos de origen deben ser únicos (Identificación: ';
$LANG['ERR_UNIQUE_IM'] = 'ERROR: Los nombres de los campos de origen deben ser únicos (Imagen: ';
$LANG['SAME_TARGET_D'] = 'ERROR: No se puede mapear el mismo campo objetivo más de una vez (';
$LANG['SAME_TARGET_ID'] = 'ERROR: No se puede mapear el mismo campo objetivo más de una vez (Identificación: ';
$LANG['SAME_TARGET_IM'] = 'ERROR: No se puede mapear el mismo campo objetivo más de una vez (Imágenes: ';
$LANG['NEED_CAT'] = 'ERROR: un valor de catalogNumber u otherCatalogNumbers es requerido para las Cargas de Archivos Esqueléticos';
$LANG['SEL_MATCH'] = 'ERROR: seleccionar cuál identificador será usado para hacer coincidir registros (requerido para Importaciones de Archivos Esqueléticos)';
$LANG['ID_NOT_MATCH'] = 'ERROR: el identificador de los registros no coincide con el de los campos importados (requerido para Importaciones de Archivos Esqueléticos)';
$LANG['SEL_TAR_USER'] = 'Ya que este es un proyecto de observaciones manejado en grupo, necesita seleccionar el usuario objetivo al cual la ocurrencia será vinculada';
$LANG['FIRST_ROW'] = '¿La primera fila del archivo contiene los nombres de las columnas? Aparentemente está mapeando directamente a la primera fila de datos activos del archivo en lugar de la fila de encabezados. De ser así, la primera fila de datos se perderá y algunas columnas pueden ser ignoradas. Seleccione OK para proceder, o cancele para abortar';
$LANG['ENTER_PROF'] = 'Entrar un nombre de perfil y haga click en el botón Guardar Mapeo para crear un nuevo Perfil de Carga';
$LANG['COL_MGMNT'] = 'Panel de Administración de Colecciones';
$LANG['LIST_UPLOAD'] = 'Lista de Perfiles de Carga';
$LANG['UP_MODULE'] = 'Módulo de Carga de Datos';
$LANG['CAUTION'] = 'Precaución';
$LANG['REC_REPLACE'] = 'Los registros que coincidan serán reemplazados por los registros cargados';
$LANG['NOT_REC'] = 'no registrado';
$LANG['UP_STATUS'] = 'Estado de Carga';
$LANG['PENDING_REPORT'] = 'Reporte de Datos Pendientes de Transferir';
$LANG['OCCS_TRANSFERING'] = 'Ocurrencias pendientes de transferir';
$LANG['PREVIEW'] = 'Ver primeros 1000 Registros';
$LANG['DOWNLOAD_RECS'] = 'Descargar Registros';
$LANG['RECORDS_UPDATED'] = 'Registros a actualizar';
$LANG['CAUTION_REPLACE'] = 'registros por cargar reemplazarán a los registros existentes';
$LANG['MISMATCHED'] = 'Registros no coincidentes';
$LANG['NEW_RECORDS'] = 'Nuevos registros';
$LANG['FAILED_LINK'] = 'Los registros no fueron vinculados a registros existentes en esta colección y no serán importados';
$LANG['MATCHING_CATALOG'] = 'Registros que coinciden con el número de catálogo que se adjuntará';
$LANG['WARNING'] = 'ADVERTENCIA';
$LANG['WARNING_DUPES'] = 'Esto resultará en registros con números de catálogo duplicados';
$LANG['RECS_SYNC'] = 'Los registros que serán sincronizados con la base de datos central';
$LANG['EXPL_SYNC'] = 'Estos son típicamente registros que han sido procesados originalmente dentro del portal, exportados e integrados en una base de datos local, y luego reimportados y sincronizados con los registros en el portal por medio del número de catálogo';
$LANG['WARNING_REPLACE'] = 'Registros por cargar reemplazarán los registros en el portal con el mismo número de catálogo. ¡Asegúrese que los registros por cargar sean los más recientes!';
$LANG['NOT_MATCHING'] = 'Registros cargados anteriormente que no coinciden con los registros entrantes';
$LANG['EXPECTED'] = 'Nota: Si está realizando una carga parcial de datos, esto es esperado';
$LANG['FULL_REFRESH'] = 'Si está haciendo una actualización de datos completa, estos pueden ser registros que fueron eliminados en su base de datos local pero no dentro del portal.';
$LANG['NULL_RM'] = 'Registros que serán eliminados debido a un Identificador Primario NULO';
$LANG['DUP_RM'] = 'Registros que serán eliminados debido a un Identificador Primario DUPLICADO';
$LANG['IDENT_TRANSFER'] = 'Conteo de historia de identificación';
$LANG['IMAGE_TRANSFER'] = 'Conteo de imágenes';
$LANG['FINAL_TRANSFER'] = '¿Está seguro que quiere transferir los datos de la tabla temporal a la tabla central de especímenes?';
$LANG['TRANS_RECS'] = 'Transferir Registros a la Tabla Central de Especímenes';
$LANG['REC_START'] = 'Inicio de Registros';
$LANG['REC_LIM'] = 'Límite de Registros';
$LANG['MATCH_CAT'] = 'Hacer Coincidir con el Número de Catálogo';
$LANG['MATCH_ON_CAT'] = 'Hacer Coincidir con Otros Números de Catálogo';
$LANG['APPENDED'] = 'Los datos esqueléticos entrantes se agregarán solo si el campo de destino está vacío';
$LANG['BOTH_CATS'] = 'Si ambas casillas son seleccionadas, las coincidencias serán realizadas primero con el número de catálogo y luego con otros números de catálogo';
$LANG['ID_SOURCE'] = 'Identificar Fuente de Datos';
$LANG['IPT_URL'] = 'URL de Recurso IPT';
$LANG['RESOURCE_URL'] = 'Dirección o URL del Recurso';
$LANG['WORKAROUND'] = 'Esta opción es para dirigirse a un archivo de datos que fue subido manualmente a un servidor. Esta opción ofrece un atajo para importar archivos que son más grandes que lo permitido por los límites de carga del servidor (e.g. límites de configuración PHP)';
$LANG['DISPLAY_OPS'] = 'Desplegar Opciones Adicionales';
$LANG['AUTOMAP'] = 'Automapear Campos';
$LANG['ANALYZE_FILE'] = 'Analizar Archivo';
$LANG['UNPROC'] = 'Sin Procesar';
$LANG['STAGE_1'] = 'Etapa 1';
$LANG['STAGE_2'] = 'Etapa 2';
$LANG['STAGE_3'] = 'Etapa 3';
$LANG['PEND_REV'] = 'Pendiente de Revisión';
$LANG['EXP_REQ'] = 'Experto Requerido';
$LANG['PEND_NFN'] = 'Pendiente de Revisión-NfN';
$LANG['SOURCE_ID'] = 'Fuente de Identificadores Únicos / Llave Primaria';
$LANG['REQ'] = 'requerido';
$LANG['IMPORT_OCCS'] = 'Importar Registros de Ocurrencia';
$LANG['VIEW_DETS'] = 'ver detalles';
$LANG['UNVER'] = 'Mapeos sin verificar están desplegados en amarillo';
$LANG['CUSTOM_FILT'] = 'Filtros de Importación de Ocurrencias Personalizados';
$LANG['FIELD'] = 'Campo';
$LANG['SEL_FIELD'] = 'Seleccionar Nombre del Campo';
$LANG['COND'] = 'Condición';
$LANG['VALUE'] = 'Valor';
$LANG['MULT_TERMS'] = 'Añadir múltiples términos separados por un punto y coma será filtrado como una condición O';
$LANG['IMPORT_ID'] = 'Importar Historia de Identificación';
$LANG['UNVER'] = 'Mapeos sin verificar son desplegados en amarillo';
$LANG['NOT_IN_DWC'] = 'no presente en el Archivo DwC';
$LANG['IMP_IMG'] = 'Importar Imágenes';
$LANG['RESET_MAP'] = 'Restaurar Mapeo de Campos';
$LANG['NEW_PROF_TITLE'] = 'Título del nuevo perfil';
$LANG['TARGET_USER'] = 'Usuario Objetivo';
$LANG['SEL_TAR_USER'] = 'Seleccionar Usuario Objetivo';
$LANG['VER_LINKS'] = 'Verificar enlaces de las imágenes';
$LANG['PROC_STATUS'] = 'Estado de Procesamiento';
$LANG['NO_SETTING'] = 'Dejar como está / Sin Configuración Explícita';
$LANG['UNK_ERR'] = 'Error desconocido al analizar la carga';
$LANG['NFN_IMPORT'] = 'Importación de Archivo de Notes from Nature';
$LANG['START_UPLOAD'] = 'Iniciar Carga';
$LANG['SEL_KEY'] = 'Seleccionar Fuente de Llave Primaria';
$LANG['SKIPPED'] = 'El registro será ignorado cuando todos los campos siguientes estén vacíos: catalogNumber, otherCatalogNumbers, occurrenceID, recordedBy (colector), eventDate, scientificName, dbpk';
$LANG['LEARN_MORE'] = 'Para aprender más acerca de mapear campos de Symbiota (y Darwin Core)';
$LANG['LOADING_DATA'] = 'Cargar Datos a Symbiota';
$LANG['VER_MAPPING'] = 'Verificar Mapeo';
$LANG['SAVE_MAP'] = 'Guardar Mapeo';
$LANG['VERSION_DATA_CHANGES'] = 'Version data changes';
$LANG['VER_LINKS_MEDIA'] = 'Verificar enlaces de imágenes en el campo associatedMedia';
$LANG['SKEL_EXPLAIN'] = 'Los Archivos Esqueléticos consisten en un conjunnto de datos que son fáciles de capturar por lote durante el procesamiento de imágenes.
	Los datos son utilizados para crear nuevos registros a los cuales las imágenes son vinculados.
	Los archivos esqueléticos típicamente colectados incluyen quién creó el registro, nombre científico, país, estado/provincia, y a veces condado, aunque cualquier campo puede ser incluído.
	Las cargas de archivos esqueléticos son muy similares a las cargas regulares aunque varían en distintos aspectos.';
$LANG['SKEL_EXPLAIN_P1'] = 'Las cargas generales de archivos típicamente consisten en registros completos, mientras que las cargas de archivos esqueléticos casi siempre serán registros anotados con datos de sólo algunos de los campos';
$LANG['SKEL_EXPLAIN_P2'] = 'El campo de número de catálogo es requerido para cargas de archivos esqueléticos ya que este campo es utilizado para encontrar coincidencias con imágenes o registros existentes';
$LANG['SKEL_EXPLAIN_P3'] = 'En casos donde el registro ya exista, una carga general de archivos reemplazará por completo al registro existente con los datos del nuevo registro.
							Por otro lado, una carga esquelética actualizará el registro existente únicamente con los nuevos datos en los campos. Los campos únicament son añadidos si aún no hay datos en el campo objetivo.';
$LANG['SKEL_EXPLAIN_P4'] = 'Si un registro NO existe aún, un nuevo registro será creado en ambos casos, pero sólo el registro esquelético será marcado como no procesado';
$LANG['NOT_AUTH'] = 'ERROR: no está autorizado para cargar archivos en esta colección';
$LANG['PAGE_ERROR'] = 'ERROR: O intentó llegar a esta página sin pasar por el menú de administración de colecciones o trató de subir un archivo que es muy grande.
	Tal vez quiera dividir el archivo en archivos más pequeños, o comprimirlo en un archivo zip  (extensión .zip).
	Puede contactar al administrador del portal para solicitar asistencia para cargar el archivo (pista para el admin: incrementar el límite de carga PHP puede ayudar, current upload_max_filesize';
$LANG['USE_BACK'] = 'Use las flechas para regresar a la página de carga de archivos.';
$LANG['UPLOAD'] = 'Subir archivo';

?>