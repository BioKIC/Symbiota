<?php
/*
------------------
Language: Español (Spanish)
Translated by: Samanta Orellana (2022-01-09)
Translated by: Google Translate (2024-09-13)
------------------
*/

$LANG['OCC_EXP_MAN'] = 'Administrador de Exportación de Ocurrencias';
$LANG['NEW_RECORDS_PROC_STATUS'] = 'Los nuevos registros no pueden tener un estado de procesamiento sin procesar o indefinido. Por favor seleccione un estado de procesamiento válido.';
$LANG['EXP_TYPE'] = 'Tipo de Exportación';
$LANG['CUSTOM_EXP'] = 'Exportación Personalizada';
$LANG['GEO_EXP'] = 'Exportación de Georreferenciación';
$LANG['EXPORT_EXPLAIN'] = 'Este módulo de descarga está diseñado para asistir a los encargados de las colecciones en extraer datos de especímenes				para importar en sistemas de manejo o investigación locales.';
$LANG['MORE'] = 'más información';
$LANG['EXPORT_EXPLAIN_2'] = 'El módulo de exportación es particularmente útil para extraer datos que han sido añadidos					utilizando las herramientas de digitalización construídas dentro del portal web (crowdsourcing, OCR/NLP, entrada de datos básica, etc).					Los registros importados de una base de datos local están vinculados al registro primario					por medio de un identificador único de cada espécimen (código de barras, clave primaria, UUID, etc).					Este identificacor está almacenado en la base de datos del portal web y le da a los administradores de colección la habilidad de actualizar los datos locales					con la información añadida dentro del portal web.					Los nuevos registros digitalizados directamente dentro del portal web (p.e. flujo de trabajo de imagen a registro) tendrán un identificador único nulo,					lo cual identifica al registro como nuevo y aún no sincronizado con la base de datos central.					Cuando los registros nuevos son extraídos del portal, son importados hacia la base de datos central,					y luego los datos de la colección snapshot en el portal serán refrescados, el número de catálogo será utilizado para sincronizar automáticamente					los registros de especímenes del portal con los de la base de datos central. Notar que la sincronización únicamente funcionará si el identificador primario es					único (p.e. no hay duplicados) dentro de la base de datos local central.';
$LANG['EXPORT_BATCH_GEO'] = 'Exportar Datos Georreferenciados por Lote';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_1'] = 'Este módulo extrae los datos de coordenadas solamente para los registros que han sido georreferenciados usando las';
$LANG['BATCH_GEO_TOOLS'] = 'herramientas de georreferenciación por lote';
$LANG['EXPORT_BATCH_GEO_EXPLAIN_2'] = 'o las herramientas de la Comunidad de GeoLocate.					Estas descargas están particularmente diseñadas para importar las nuevas coordenadas a la base de datos local.					Si ningún registro ha sido georreferenciado dentro del portal, el archivo generado estará vacío.';
$LANG['PROCESSING_STATUS'] = 'Estado de Procesamiento';
$LANG['ALL_RECORDS'] = 'Todos Registros';
$LANG['COMPRESSION'] = 'Compresión';
$LANG['ARCHIVE_DATA_PACK'] = 'Paquete de Archivos de Datos (archivo ZIP)';
$LANG['FILE_FORMAT'] = 'Formato del Archivo';
$LANG['CSV'] = 'Delimitado por Comas (CSV)';
$LANG['TAB_DELIMITED'] = 'Delimitado por Tabulaciones';
$LANG['CHAR_SET'] = 'Conjunto de Caracteres';
$LANG['EXPORT_LACKING_GEO'] = 'Exportar Especímenes Carentes de Datos de Georreferenciación';
$LANG['EXPORT_LACKING_GEO_EXPLAIN'] = 'Este módulo extrae especímenes que carecen de coordenadas decimales o tienen coordenadas que necesitan ser verificadas.					Esta descarga resultará en un Archivo Darwin Core con un archivo CSV codificado como UTF-8, conteniendo					únicamente las columnas de datos de georreferenciación relevantes para las ocurrencias. Por defecto, las ocurrencias					serán limitadas a registros conteniendo información de localidad pero no coordenadas decimales.					Este resultado es particularmente útil para generar extractos de datos que serán georreferenciados usando herramientas externas.';
$LANG['COORDINATES'] = 'Coordenadas';
$LANG['ARE_EMPTY'] = 'está vacío (es nulo)';
$LANG['HAVE_VALUES'] = 'tiene valores (p.e. necesitan verificación)';
$LANG['ADDITIONAL_FILTERS'] = 'Filtros<br/>Adicionales';
$LANG['SELECT_FIELD'] = 'Seleccionar Campo';
$LANG['DOWNLOAD_RECORDS'] = 'Descargar Registros';
$LANG['DOWNLOAD_SPEC_RECORDS'] = 'Descargar Registros de Especímenes';
$LANG['NEW_RECORDS_ONLY'] = 'Únicamente Registros Nuevos';
$LANG['EG_IN_PORTAL'] = '(p.e. registros procesados dentro del portal)';
$LANG['MORE_INFO'] = 'Más Infrormación';
$LANG['MORE_INFO_TEXT'] = 'Limitar a registros nuevos ingresados y procesados directamente dentro del					portal, que no han sido directamente importados y sincronizados con la					base de datos central. Evitar importar datos esqueléticos sin procesar ya que					futuras importaciones de datos necesitarán una coordinación más compleja.';
$LANG['TRAIT_FILTER'] = 'Filtro<br/>de Características de Ocurrencia';
$LANG['OR_SPEC_ATTRIBUTE'] = 'O seleccionar un Estado de Atributo específico';
$LANG['HOLD_CTRL'] = 'Presione la tecla control (ctrl) o comando para seleccionar múltiples opciones';
$LANG['STRUCTURE'] = 'Estructura';
$LANG['SYMB_NATIVE'] = 'Nativo de Symbiota';
$LANG['SYMB_NATIVE_EXPLAIN'] = 'Los archivos nativos de Symbiota son muy similares a Darwin Core, con excepción de la adición de algunos campos 					como sustrato, colectores asociados, descripción literal.';
$LANG['DWC_EXPLAIN'] = 'Darwin Core es un estándar específico para conjuntos de datos de biodiversidad, respaldado por TDWG.					Para más información, visite el sitio web de <a href=';
$LANG['DATA_EXTENSIONS'] = 'Extensiones de Datos';
$LANG['INCLUDE_DET'] = 'incluir Historia de Determinación';
$LANG['INCLUDE_IMAGES'] = 'incluir registros medios';
$LANG['INCLUDE_ATTRIBUTES'] = 'incluir Atributos de Características de Ocurrencia (extensión MeasurementOrFact)';
$LANG['OUTPUT_COMPRESSED'] = 'El archivo generado debe ser comprimido';
$LANG['ACCESS_DENIED'] = 'Acceso denegado';
$LANG['INCLUDE_ASSOCIATIONS'] = 'incluir Relaciones de Recursos (asociaciones y recursos vinculados)';

?>
