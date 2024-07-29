<?php
/*
------------------
Language: Español
Date Translated: 2023-12-19
Translated by: Google Translate
------------------
*/

// duplicateharvest.php

$LANG['DUP_GEOREFERENCE'] = 'Georreferenciación duplicada';
$LANG['COL_MNG'] = 'Gestión de colecciones';
$LANG['BATCH_HARVEST_DUP'] = 'Recolección por lotes de duplicados';
$LANG['STAGING_VARIABLES'] = 'Variables de preparación';
$LANG['TARGET_FIELDS'] = 'Campos de destino';
$LANG['ALL_FIELDS'] = 'Todos los campos';
$LANG['GEO_FIELDS'] = 'Campos de georeferencia';
$LANG['MATCH_METHOD'] = 'Método de coincidencia';
$LANG['DUP_SPEC_TABLES'] = 'Tablas de muestras duplicadas';
$LANG['EXS_TABLES'] = 'Tablas exsiccatae';
$LANG['REC_NOT_EVAL_SINCE'] = 'Registros no evaluados desde';
$LANG['PROC_STATUS'] = 'Estado de procesamiento';
$LANG['ALL_RECS'] = 'Todos los registros';
$LANG['STAGE_1'] = 'Etapa 1';
$LANG['STAGE_2'] = 'Etapa 2';
$LANG['STAGE_3'] = 'Etapa 3';
$LANG['UNPROCESSED'] = 'Sin procesar';
$LANG['BUILD_LIST'] = 'Lista de compilación';
$LANG['REC_LIMIT'] = 'Límite de registros';
$LANG['COLL_CODE'] = 'Código<br/>Colección';
$LANG['CAT_BR_NUM'] = 'Número de catálogo<br/>';
$LANG['NOT_AUTH'] = 'No estás autorizado a acceder a esta página';

// imageprocessor.php

$LANG['IMG_PROCESSOR'] = 'Procesador de imágenes';
$LANG['SEL_IMPORT_TYPE'] = 'Se debe seleccionar el tipo de importación/mapeo de imagen';
$LANG['USE_DEFAULT_PATH'] = "-- Usar Ruta Predeterminada --";
$LANG['SEL_CSV'] = 'Seleccione un archivo CSV para cargar';
$LANG['CSV_OR_ZIP'] = 'El archivo de entrada debe ser un archivo delimitado por comas (CSV) o un archivo ZIP que contenga un archivo CSV';
$LANG['NEED_PATTERN_MATCH'] = 'El término de coincidencia de patrón debe tener un valor';
$LANG['CATNUM_IN_PARENS'] = 'La parte del catálogo del término de coincidencia de patrones debe estar entre paréntesis';
$LANG['WEB_IMG_NUMERIC'] = 'El ancho de la imagen dividida en la Web sólo puede ser un valor numérico';
$LANG['TN_IMG_NUMERIC'] = 'El ancho de la imagen en miniatura sólo puede ser un valor numérico';
$LANG['LG_IMG_NUMERIC'] = 'El ancho de una imagen grande sólo puede ser un valor numérico';
$LANG['TITLE_NOT_EMPTY'] = 'El título no puede estar vacío';
$LANG['JPG_BETWEEN'] = 'La compresión JPG debe ser un valor numérico entre 30 y 100';
$LANG['PROC_DATE_FORMAT'] = 'La fecha de inicio del procesamiento debe tener el formato AAAA-MM-DD (por ejemplo, 2023-02-14)';
$LANG['CHECK_MATCH_TERM'] = 'Es necesario marcar al menos una de las casillas de verificación de Término coincidente';
$LANG['TARGET_MUST_UNIQUE'] = 'ERROR: Los nombres de los campos de destino deben ser únicos (campo duplicado';
$LANG['SOURCE_MUST_UNIQUE'] = 'ERROR: Los nombres de los campos de origen deben ser únicos (campo duplicado';
$LANG['MUST_MAP_CATNUM'] = 'El número de catálogo u otros números de catálogo deben asignarse a un campo de importación';
$LANG['LARGE_URL_MAPPED'] = 'La URL de imagen grande debe estar asignada a un campo de importación';
$LANG['IMG_PROCESSOR_EXPLAIN'] = 'Estas herramientas están diseñadas para ayudar a los administradores de colecciones en el procesamiento por lotes de imágenes de muestras. Póngase en contacto con el administrador del portal para obtener ayuda para configurar un nuevo flujo de trabajo.
			Una vez que se establece un perfil, el administrador de la colección puede usar este formulario para activar manualmente el procesamiento de imágenes. Para obtener más información, consulte la documentación de Symbiota para
			<b><a href="https://biokic.github.io/symbiota-docs/coll_manager/images/batch/" target="_blank">prácticas recomendadas</a></b> para integrar imágenes.' ;
$LANG['IMG_FILE_UPLOAD_MAP'] = 'Mapa de carga de archivos de imagen';
$LANG['SOURCE_FIELD'] = 'Campo de origen';
$LANG['TARGET_FIELD'] = 'Campo de destino';
$LANG['SEL_TARGET_FIELD'] = 'Seleccionar campo de destino';
$LANG['CAT_NUM'] = 'Número de catálogo';
$LANG['OTHER_CAT_NUMS'] = 'Otros números de catálogo';
$LANG['LG_IMG_URL'] = 'URL de imagen grande (obligatorio)';
$LANG['WEB_IMG_URL'] = 'URL de la imagen web';
$LANG['TN_URL'] = 'URL en miniatura';
$LANG['SOURCE_URL'] = 'URL de origen';
$LANG['LINK_BLANK_RECORD'] = 'Vincular imagen a nuevo registro en blanco si el número de catálogo no existe';
$LANG['MAP_IMGS'] = 'Imágenes del mapa';
$LANG['SAVED_PROCESSING_PROF'] = 'Perfiles de procesamiento de imágenes guardados';
$LANG['EDIT'] = 'Editar';
$LANG['NEW'] = 'Nuevo';
$LANG['PROFILE'] = 'Perfil';
$LANG['CLOSE_EDITOR'] = 'Cerrar editor';
$LANG['PROC_TYPE'] = 'Tipo de procesamiento';
$LANG['MAP_FROM_SERVER'] = 'Imágenes de mapa desde un servidor local o remoto';
$LANG['URL_MAP_FILE'] = 'Archivo de mapeo de URL de imagen';
$LANG['TITLE'] = 'Título';
$LANG['PATT_MATCH_TERM'] = 'Término de coincidencia de patrón';
$LANG['MORE_INFO'] = 'Más información';
$LANG['PATTERN_EXPLAIN'] = 'Expresión regular necesaria para extraer el identificador único del texto fuente.
			Por ejemplo, la expresión regular /^(WIS-L-\d{7})\D*/ extraerá el número de catálogo WIS-L-0001234.
			del archivo de imagen llamado WIS-L-0001234_a.jpg. Para obtener más información sobre la creación de expresiones regulares,
			busque en Internet &quot;Tutorial PHP de expresiones regulares&quot;';
$LANG['REPLACEMENT_TERM'] = 'Término de reemplazo';
$LANG['OPTIONAL'] = 'Opcional';
$LANG['PATT_REPLACE_EXPLAIN'] = "La expresión regular opcional para la coincidencia del número de catálogo se reemplazará con un término de reemplazo.
			Ejemplo 1: expresión reemplazar término = '/^/' combinada con reemplazar cadena = 'código de barras-' convertirá 0001234 => código de barras-0001234.
			Ejemplo 2: expresión reemplazar término = '/XYZ-/' combinada con una cadena de reemplazo vacía convertirá XYZ-0001234 => 0001234.";
$LANG['REPLACE_EXPLAIN'] = 'Cadena de reemplazo opcional para solicitar coincidencias de términos de reemplazo de expresión en catalogNumber.';
$LANG['IMG_SOURCE_PATH'] = 'Ruta de origen de la imagen';
$LANG['IMG_TARGET_PATH'] = 'Ruta de destino de la imagen';
$LANG['TARGET_PATH_EXPLAIN'] = "Ruta del servidor web donde se depositarán los derivados de la imagen.
			El servidor web (por ejemplo, el usuario de Apache) debe tener acceso de lectura/escritura a este directorio.
			Si este campo se deja en blanco, se utilizará la imagen de destino predeterminada del portal (\$IMAGE_ROOT_PATH).";
$LANG['IMG_URL_BASE'] = 'Base de URL de imagen';
$LANG['IMG_URL_EXPLAIN'] = "Prefijo de URL de imagen que accederá a la carpeta de destino desde el navegador.
			Esto se utilizará para crear las URL de las imágenes que se almacenarán en la base de datos.
			Si se proporciona la URL absoluta sin el nombre de dominio, se asumirá el dominio del portal.
			Si este campo se deja en blanco, se utilizará la URL de la imagen predeterminada del portal (\$IMAGE_ROOT_URL).";
$LANG['WEB_IMG_WIDTH'] = 'Ancho de imagen de tamaño web';
$LANG['WEB_IMG_EXPLAIN'] = 'Ancho de la imagen web estándar en píxeles. Si la imagen de origen es menor que este ancho, el archivo simplemente se copiará sin cambiar el tamaño.';
$LANG['TN_IMG_WIDTH'] = 'Ancho de la imagen en miniatura';
$LANG['TN_IMG_EXPLAIN'] = 'Ancho de la miniatura de la imagen en píxeles. El ancho debe ser mayor que el tamaño de la imagen dentro de las páginas de visualización de miniaturas.';
$LANG['LG_IMG_WIDTH'] = 'Ancho de imagen grande';
$LANG['LG_IMG_EXPLAIN'] = 'Ancho de la versión grande de la imagen en píxeles.
			Si la imagen de origen es más pequeña que este ancho, el archivo simplemente se copiará sin cambiar el tamaño.
			Tenga en cuenta que el cambio de tamaño de imágenes grandes puede estar limitado por los ajustes de configuración de PHP (por ejemplo, límite_memoria).
			Si esto es un problema, tener este valor mayor que el ancho máximo de las imágenes de origen evitará
			errores relacionados con el remuestreo de imágenes grandes.';
$LANG['JPG_QUALITY'] = 'Calidad JPG';
$LANG['JPG_QUALITY_EXPLAIN'] = 'La calidad JPG se refiere a la cantidad de compresión aplicada.
			El valor debe ser numérico y oscilar entre 0 (peor calidad, archivo más pequeño) y
			99 (mejor calidad, archivo más grande). No utilice 100; aumentará erróneamente el tamaño de su imagen.
			Si es nulo, se utiliza 75 como valor predeterminado.';
$LANG['THUMBNAIL'] = 'Miniatura';
$LANG['CREATE_NEW_TN'] = 'Crear nueva miniatura a partir de la imagen de origen';
$LANG['IMPORT_TN_SOURCE'] = 'Importar miniatura desde la ubicación de origen (nombre de origen con sufijo _tn.jpg)';
$LANG['MAP_TN_AT_SOURCE'] = 'Asignar a la miniatura en la ubicación de origen (nombre de origen con sufijo _tn.jpg)';
$LANG['EXCLUDE_TN'] = 'Excluir miniatura';
$LANG['LG_IMG'] = 'Imagen grande';
$LANG['IMPORT_LG_SOURCE'] = 'Importar imagen fuente como versión grande';
$LANG['MAP_TO_LG_SOURCE'] = 'Asignar a la imagen de origen como versión grande';
$LANG['IMPORT_LG_FROM_SOURCE'] = 'Importar versión grande desde la ubicación de origen (nombre de origen con sufijo _lg.jpg)';
$LANG['MAP_LG_AT_SOURCE'] = 'Asignar a la versión grande existente (nombre de fuente con sufijo _lg.jpg)';
$LANG['EXCLUDE_LG'] = 'Excluir versión grande';
$LANG['SEL_URL_MAP_FILE'] = 'Seleccionar archivo de mapeo de URL';
$LANG['CHOOSE_FILE'] = 'Elegir archivo';
$LANG['SAVE_PROFILE'] = 'Guardar perfil';
$LANG['SURE_DELETE_PROF'] = '¿Está seguro de que desea eliminar este perfil de procesamiento de imágenes?';
$LANG['DELETE_PROJ'] = 'Eliminar proyecto';
$LANG['DELETE_PROF'] = 'Eliminar perfil';
$LANG['SHOW_ALL_OR_ADD'] = 'Mostrar todos los perfiles guardados o agregar uno nuevo...';
$LANG['OPEN_EDITOR'] = 'Abrir editor';
$LANG['NO_RUN_DATE'] = 'sin fecha de ejecución';
$LANG['LAST_RUN_DATE'] = 'Fecha de la última ejecución';
$LANG['PROC_START_DATE'] = 'Fecha de inicio del procesamiento';
$LANG['REPLACEMENT_STR'] = 'Cadena de reemplazo';
$LANG['SOURCE_PATH'] = 'Ruta de origen';
$LANG['TARGET_FOLDER'] = 'Carpeta de destino';
$LANG['URL_PREFIX'] = 'Prefijo de URL';
$LANG['WEB_IMG'] = 'Imagen web';
$LANG['EVALUATE_IMPORT_SOURCE'] = 'Evaluar e importar imagen de origen';
$LANG['IMPORT_WITHOUT_RESIZE'] = 'Importar la imagen de origen tal como está sin cambiar el tamaño';
$LANG['MAP_SOURCE_NO_IMPORT'] = 'Asignar a la imagen de origen sin importar';
$LANG['UNABLE_MATCH_ID'] = 'No se puede hacer coincidir el identificador principal con un registro de base de datos existente';
$LANG['MISSING_RECORD'] = 'Falta registro';
$LANG['SKIP_AND_NEXT'] = 'Omitir la importación de imágenes y pasar a la siguiente';
$LANG['CREATE_AND_LINK'] = 'Crear registro vacío e imagen de enlace';
$LANG['IMG_EXISTS'] = 'La imagen ya existe';
$LANG['SKIP_IMPORT'] = 'Omitir importación';
$LANG['RENAME_SAVE_BOTH'] = 'Cambiar el nombre de la imagen y guardar ambas';
$LANG['REPLACE_EXISTING'] = 'Reemplazar imagen existente';
$LANG['LOOK_FOR_SKELETAL'] = 'Buscar y procesar archivos esqueléticos (extensiones permitidas: csv, txt, tab, dat)';
$LANG['SKIP_SKELETAL'] = 'Omitir archivos esqueléticos';
$LANG['PROCESS_SKELETAL'] = 'Procesar archivos esqueléticos';
$LANG['COLLID_NOT_DEFINED'] = 'ERROR: identificador de colección no definido. Administrador de contacto';
$LANG['LOG_FILES'] = 'Archivos de registro';
$LANG['GEN_PROCESSING'] = 'Procesamiento general';
$LANG['IPLANT'] = 'iPlant (anterior a CyVerse)';
$LANG['CYVERSE'] = 'CyVerse';
$LANG['IMG_MAP_FILE'] = 'Archivo de mapeo de imágenes';
$LANG['NO_LOGS'] = 'No existen registros para esta colección';

// nlpprocessor.php
$LANG['NLP_PROCESSOR'] = 'Procesador PNL';
$LANG['UNPROCESSED_SPECS'] = 'Especímenes sin procesar';
$LANG['UNPROCESSED_SPECS_NO_IMGS'] = 'Especímenes sin procesarEspecímenes sin procesar sin imágenes';
$LANG['UNPROCESSED_SPECS_NO_OCR'] = 'Especímenes sin procesar sin OCR';
$LANG['NO_UNPROCESSED'] = 'No hay registros sin procesar';
$LANG['UNIDENTIFIED_ERROR'] = 'Error no identificado';

// ocrprocessor.php
$LANG['PLS_SEL_PROC_STATUS'] = 'Por favor seleccione un estado de procesamiento';
$LANG['ENTER_PATT_MATCH'] = 'Ingrese una cadena que coincida con el patrón para extraer el número de catálogo';
$LANG['SEL_OCR_INPUT'] = 'Seleccione/ingrese un archivo fuente de entrada OCR';
$LANG['UPLOAD_MUST_ZIP'] = 'El archivo cargado debe ser un archivo ZIP con extensión .zip';
$LANG['SPEC_IMG_STATS'] = 'Estadísticas de imágenes de muestras';
$LANG['TOTAL_W_IMGS'] = 'Total de especímenes con imágenes';
$LANG['SPEC_W_IMGS'] = 'muestras con imágenes';
$LANG['W_OCR'] = 'con OCR';
$LANG['WO_OCR'] = 'sin OCR';
$LANG['CUSTOM_QUERY'] = 'Consulta personalizada';
$LANG['SEL_PROC_STATUS'] = 'Seleccionar estado de procesamiento';
$LANG['NO_STATUS'] = 'Sin estado';
$LANG['RESET_STATS'] = 'Restablecer estadísticas';
$LANG['BATCH_OCR_IMGS'] = 'Imágenes OCR por lotes utilizando el motor Tesseract OCR';
$LANG['PROC_STATUS'] = 'Estado de procesamiento';
$LANG['UNPROCESSED'] = 'sin procesar';
$LANG['NUM_RECORDS_PROCESS'] = 'Número de registros a procesar';
$LANG['RUN_BATCH_OCR'] = 'Ejecutar OCR por lotes';
$LANG['TESSERACT_DEPEND'] = 'Nota: Esta característica depende de la instalación adecuada del motor Tesseract OCR en el servidor de alojamiento';
$LANG['NO_TESSERACT'] = 'El motor Tesseract OCR no parece estar instalado o la variable tesseractPath no está configurada en el archivo de configuración de Symbiota. ';
$LANG['CONTACT_SYSADMIN'] = 'Póngase en contacto con el administrador de su sistema para resolver estos problemas.';
$LANG['OCR_IMPORT_TOOL'] = 'Herramienta de importación por lotes de OCR';
$LANG['OCR_IMPORT_EXPLAIN'] = 'Esta interfaz cargará archivos de texto OCR generados fuera del entorno del portal.
			Por ejemplo, ABBYY FineReader tiene la capacidad de procesar por lotes imágenes de muestras de OCR y generar los resultados como archivos de texto separados (.txt) con el nombre de la imagen de origen.
			Los archivos de texto OCR se vinculan a registros de muestras haciendo coincidir los números de catálogo extraídos del nombre del archivo y comparando los nombres de los archivos OCR y de imagen.';
$LANG['REQS'] = 'Requisitos';
$LANG['REQ1'] = 'Los archivos OCR deben estar en formato de texto con una extensión .txt. Cuando utilice ABBYY, utilice la configuración: "Crear un documento separado para cada archivo", "Guardar como texto (*.txt)" y "Nombre como archivo fuente"';
$LANG['REQ2'] = 'Comprimir varios archivos de texto OCR en un único archivo zip para cargarlo en el portal';
$LANG['REQ3'] = 'Los archivos deben nombrarse utilizando el número de catálogo. La siguiente expresión regular se utilizará para extraer el número de catálogo del nombre del archivo. Haga clic en el símbolo de información para obtener más información.';
$LANG['REQ4'] = 'Dado que el texto OCR debe estar vinculado a la imagen de origen, las imágenes deben haberse cargado previamente en el portal';
$LANG['REQ5'] = 'Si hay más de una imagen vinculada a una muestra, se utilizará el nombre completo del archivo para determinar qué imagen vincular en el OCR';
$LANG['REGEX'] = 'Expresión regular';
$LANG['REGEX_EXPLAIN'] = 'Expresión regular necesaria para extraer el identificador único del texto fuente.
			Por ejemplo, la expresión regular /^(WIS-L-\d{7})\D*/ extraerá el número de catálogo WIS-L-0001234.
			del archivo de imagen llamado WIS-L-0001234_a.jpg. Para obtener más información sobre la creación de expresiones regulares,
			busque en Internet "Tutorial PHP de expresiones regulares". Se recomienda tener el administrador del portal
			ayuda con la configuración inicial del procesamiento por lotes.';
$LANG['ZIP_W_OCR'] = 'Archivo zip que contiene OCR';
$LANG['TOGGLE_FULL_PATH'] = 'alternar opción para ingresar la ruta completa';
$LANG['FULL_PATH'] = 'opción de ruta completa';
$LANG['BROWSE_SEL_ZIP'] = 'Examine y seleccione el archivo zip que contiene múltiples archivos de texto OCR.';
$LANG['SOURCE_PATH_EXPLAIN'] = 'Ruta del archivo o URL a la carpeta que contiene los archivos de texto OCR.
			Si se proporciona una URL (por ejemplo, http://), el servidor web debe configurarse para enumerar
			todos los archivos dentro del directorio, o la salida html debe enumerar todas las imágenes en etiquetas de anclaje.
			Los scripts intentarán rastrear todos los directorios secundarios.';
$LANG['OCR_SOURCE'] = 'Fuente de OCR';
$LANG['OCR_SOURCE_EXPLAIN'] = 'Cadena corta que describe la fuente de OCR (por ejemplo, ABBYY, Tesseract, etc.). Este valor se coloca en el campo fuente con la fecha actual adjunta.';
$LANG['LOAD_OCR_FILES'] = 'Cargar archivos OCR';

?>
