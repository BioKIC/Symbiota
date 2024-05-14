<?php
/*
------------------
Language: Español
Translated by: Samanta Orellana
Date Translated: 2022-01-09
------------------
*/

include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrencetabledisplay.en.php');

$LANG['GEOREF_TOOLS'] = 'Herramientas de Georreferenciación';
$LANG['COL_MAN_MENU'] = 'Menú de Administración de Colecciones';
$LANG['SPEC_MANAGEMENT'] = 'Administración de Especímenes';
$LANG['BATCH_GEO_TOOLS'] = 'Herramientas de Georreferenciación por Lote';
$LANG['MULT_COL_SELECT'] = 'Seleccionador de Colecciones Múltiples';
$LANG['SEL_DESEL_ALL'] = 'Seleccionar / Deseleccionar Todo';
$LANG['EVAL_COLLS'] = 'Evaluar Colecciones';
$LANG['ONLY_ADMIN_COLS'] = 'Únicamente colecciones con acceso administrativo activo son desplegadas';
$LANG['QUERY_FORM'] = 'Formulario de Búsqueda';
$LANG['ALL_COUNTRIES'] = 'Todos los Países';
$LANG['ALL_STATES'] = 'Todos los Estados';
$LANG['ALL_COUNTIES'] = 'Todos los Condados';
$LANG['ALL_MUNS'] = 'Todas las Municipalidades';
$LANG['ALL_PROC_STAT'] = 'Todos los Estados de Procesamiento';
$LANG['ADVANCED_OPT'] = 'Opciones Avanzadas';
$LANG['VERIF_STATUS'] = 'Estado de Verificación';
$LANG['FAMILY_GENUS'] = 'Familia/Género';
$LANG['INCLUDE_PREV_GEOREF'] = 'Incluir registros georreferenciados previamente';
$LANG['GENERATE_LIST'] = 'Generar Listado';
$LANG['LOCALITY_TERM'] = 'Término de Localidad';
$LANG['SEARCH_CLONES'] = 'Buscar por clones georreferenciados previamente';
$LANG['GEOLOCATE_LOCALITY'] = 'Ubicar localidad con GeoLocate';
$LANG['ANALYZE_FOR_COORDS'] = 'Analizar campo de Localidad por Lat/Long o UTM insertas';
$LANG['EDIT_FIRST_SET'] = 'Editar primer conjunto de registros';
$LANG['LIMIT_REACHED'] = 'límite alcanzado (no todas las localidades disponibles desplegadas)';
$LANG['RETURN_COUNT'] = 'Desplegar Conteo';
$LANG['NO_LOCALITIES_RETURNED'] = 'Ninguna localidad que coincida con el término fue encontrada';
$LANG['USE_QUERY_FORM]'] = 'Usar formulario de búsqueda de arriba para construir listado de localidades';
$LANG['STATISTICS'] = 'Estadísticas';
$LANG['RECS_TO_GEOREF'] = 'Registros a ser Georeferenciados';
$LANG['TOTAL'] = 'Total';
$LANG['PERCENT'] = 'Porcentaje';
$LANG['DEG'] = 'Gra'; //as in degrees
$LANG['MIN'] = 'Min'; //as in minutes
$LANG['SEC'] = 'Seg'; //as in seconds
$LANG['DECIMAL'] = 'Decimal';
$LANG['LATITUDE'] = 'Latitud';
$LANG['N'] = 'N'; //as in north
$LANG['S'] = 'S'; //as in south
$LANG['LONGITUDE'] = 'Longitude';
$LANG['E'] = 'E'; //as in east
$LANG['W'] = 'O'; //as in west
$LANG['ERROR_METERS'] = 'Error (en metros)';
$LANG['DATUM'] = 'Datum';
$LANG['FOOTPRINT_WKT'] = 'Footprint WKT';
$LANG['EAST'] = 'Este';
$LANG['NORTH'] = 'Norte';
$LANG['ZONE'] = 'Zona';
$LANG['HEMISPHERE'] = 'Hemisferio';
$LANG['SOUTH'] = 'Sur';
$LANG['CONVERT_UTMS'] = 'Convertir valores UTM a lat/long';
$LANG['SOURCES'] = 'Fuentes';
$LANG['PROTOCOLS'] = 'Protocolos';
$LANG['REMARKS'] = 'Comentarios';
$LANG['ELEVATION'] = 'Elevación';
$LANG['TO'] = 'a';
$LANG['METERS'] = 'metros';
$LANG['FEET'] = 'pies';
$LANG['PROCESSING_STATUS'] = 'Estado de Procesamiento';
$LANG['LEAVE_AS_IS'] = 'Dejar Como Está';
$LANG['GEOREF_BY'] = 'Georeferenciado por';
$LANG['UPDATE_COORDS'] = 'Actualizar Coordenadas';
$LANG['NOTE_EXISTING_GEOREFS'] = 'Nota: Datos existentes en el campo de georreferencia serán reemplazados por datos entrantes.
								Sin embargo, los datos de elevación serán añadidos únicamente cuando los campos objetivo estén vacíos.
								Los campos de georreferencia que serán remplazados incluyen: decimalLatitude, decimalLongitude, coordinateUncertaintyInMeters, geodeticdatum,
								footprintwkt, georeferencedby, georeferenceRemarks, georeferenceSources, georeferenceProtocol, georeferenceVerificationStatus';
$LANG['ERROR_NO_PERMISSIONS'] = 'ERROR: No tiene permiso para editar esta colección';
$LANG['BATCH_GEO_TOOL'] = 'Herramienta de Georreferenciación por Lote';
$LANG['COL_SELECTOR'] = 'Seleccionador de Colecciones';
$LANG['ERROR_COL_ID_NULL'] = 'ERROR: Identificador de la colección está vacío';

$LANG['TABLE_CONTENT'] = 'Formulario de actualización de datos geográficos: este formulario facilita la modificación de coordenadas, incluyendo latitud, longitud y parámetros adicionales.';
$LANG['MULT_COLL_DIV'] = 'División de colección múltiple';
$LANG['LOCALLIST'] = 'Lista local';
$LANG['LATITUDE_DEGREE'] = 'Grados de latitud';
$LANG['LATITUDE_MINUTES'] = 'Minutos de latitud';
$LANG['LATITUDE_SECONDS'] = 'Segundos de latitud';
$LANG['LATITUDE_NORTH_SOUTH'] = 'Latitud Norte/Sur';
$LANG['DECIMAL_LATITUDE'] = 'Latitud decimal';
$LANG['LONGITUDE_DEGREE'] ='Grados de longitud';
$LANG['LONGITUDE_MINUTES'] = 'Minutos de longitud';
$LANG['LONGITUDE_SECONDS'] = 'Segundos de longitud';
$LANG['LONGITUDE_EAST_WEST'] = 'Longitud Este/Oeste';
$LANG['DECIMAL_LONGITUDE'] = 'Longitud decimal';
$LANG['MINIMUM_ELEVATION_IN_METERS'] = 'Elevación mínima en metros';
$LANG['MAXIMUM_ELEVATION_IN_METERS'] = 'Elevación máxima en metros';
$LANG['MINIMUM_ELEVATION'] = 'Elevación mínima';
$LANG['MAXIMUM_ELEVATION'] = 'Elevación máxima';
$LANG['ADD_ICON'] = 'Agregar';
$LANG['WORLD_ICON'] = 'Icono mundial';
$LANG['EDIT_PLUS_ICON'] = 'Editar icono más';
$LANG['FIND_CLONES'] ='Buscar duplicados previamente geolocalizados';
$LANG['LOCATE_GEO_AREA'] = 'Ubicar el área geográfica';
$LANG['EXAMINE_FOR_COORDS'] = 'Examinar la localidad para Lat/Long o UTM.';
$LANG['INITIAL_RECORDS_EDIT'] = 'Revisar el lote inicial de registros';
$LANG['SKIP_NAV'] = 'Saltar sección de navegación';
?>
