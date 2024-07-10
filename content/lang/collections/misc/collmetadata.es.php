<?php
/*
------------------
Language: Español (Spanish)
------------------
*/

include_once($SERVER_ROOT.'/content/lang/collections/misc/sharedterms.es.php');

$LANG['ADD_SUCCESS'] = 'Nueva colección agregada exitosamente';
$LANG['ADD_STUFF'] = 'Agregar contactos, enlaces de recursos o dirección de institución a continuación';
$LANG['COL_PROFS'] = 'Perfiles de colección';
$LANG['LOADING'] = '<p>Cargando...</p>';
$LANG['META_EDIT'] = 'Editor de metadatos';
$LANG['CREATE_COLL'] = 'Crear nuevo perfil de colección';
$LANG['COL_META_EDIT'] = 'Editor de metadatos de la colección';
$LANG['CONT_RES'] = 'Contactos y recursos';
$LANG['COL_INFO'] = 'Información de la colección';
$LANG['INST_CODE'] = 'Código de institución';
$LANG['MORE_INST_CODE'] = 'Más información sobre el Código de Institución';
$LANG['NAME_ONE'] = 'El nombre (o acrónimo) utilizado por la institución que tiene la custodia de los registros de sucesos. Este campo es obligatorio. Para obtener más detalles, consulte';
$LANG['DWC_DEF'] = 'Definición de Darwin Core';
$LANG['COLL_CODE'] = 'Código de colección';
$LANG['MORE_COLL_CODE'] = 'Más información sobre el Código de Colección';
$LANG['NAME_ACRO'] = 'El nombre, acrónimo o código que identifica la colección o conjunto de datos del que se derivó el registro. Este campo es opcional. Para obtener más detalles, consulte';
$LANG['COLL_NAME'] = 'Nombre de la colección';
$LANG['DESC'] = 'Descripción (2000 caracteres como máximo)';
$LANG['HOMEPAGE'] = 'Página de inicio';
$LANG['CONTACT'] = 'Contacto';
$LANG['EMAIL'] = 'Correo electrónico';
$LANG['LAT'] = 'Latitud';
$LANG['LONG'] = 'Longitud';
$LANG['MAP'] = 'Abrir mapa';
$LANG['MORE_INFO'] = 'Mostrar más información';
$LANG['CATEGORY'] = 'Categoría';
$LANG['NO_CATEGORY'] = 'Sin categoría';
$LANG['ALLOW_PUBLIC_EDITS'] = 'Permitir ediciones públicas';
$LANG['MORE_PUB_EDITS'] = 'Más información sobre las ediciones públicas';
$LANG['EXPLAIN_PUBLIC'] = 'Verificar las ediciones públicas permitirá que cualquier usuario que haya iniciado sesión en el sistema modifique los registros de muestras
                    y resolver errores encontrados dentro de la colección. Sin embargo, si el usuario no tiene explícito
                    autorización para la colección dada, las ediciones no se aplicarán hasta que sean
                    revisado y aprobado por el administrador de la colección.';
$LANG['LICENSE'] = 'Licencia';
$LANG['MORE_INFO_RIGHTS'] = 'Más información sobre Derechos';
$LANG['ORPHANED'] = 'término huérfano';
$LANG['LEGAL_DOC'] = 'Un documento legal que otorga permiso oficial para hacer algo con el recurso.
                    Este campo se puede limitar a un conjunto de valores modificando el archivo de configuración central del portal.
                    Para obtener más detalles, consulte';
$LANG['RIGHTS_HOLDER'] = 'Titular de los derechos';
$LANG['MORE_INFO_RIGHTS_H'] = 'Más información sobre el Titular de los derechos';
$LANG['HOLDER_DEF'] = 'La organización o persona que administra o posee los derechos del recurso.
                    Para obtener más detalles, consulte';   
$LANG['ACCESS_RIGHTS'] = 'Derechos de acceso';
$LANG['MORE_INFO_ACCESS_RIGHTS'] = 'Más información sobre Derechos de Acceso';
$LANG['ACCESS_DEF'] = 'Información o un enlace URL a una página con detalles que explican
                    cómo se pueden utilizar los datos. Ver';
$LANG['DATASET_TYPE'] = 'Tipo de conjunto de datos';
$LANG['PRES_SPECS'] = 'Especímenes preservados';
$LANG['OBSERVATIONS'] = 'Observaciones';
$LANG['PERS_OBS_MAN'] = 'Gestión de observación personal';
$LANG['MORE_COL_TYPE'] = 'Más información sobre el tipo de colección';
$LANG['COL_TYPE_DEF'] = 'Los especímenes preservados significan un tipo de colección que contiene muestras físicas que son
                        disponibles para inspección por parte de investigadores y expertos taxonómicos. Utilice Observaciones cuando el registro no se base en una muestra física.
                        La Gestión de Observación Personal es un conjunto de datos donde los usuarios registrados
                        puede gestionar de forma independiente su propio subconjunto de registros. Los registros ingresados en este conjunto de datos están vinculados explícitamente al perfil del usuario.
                        y sólo pueden ser editados por ellos. Este tipo de colección
                        Normalmente lo utilizan los investigadores de campo para gestionar los datos de su recopilación e imprimir etiquetas.
                        antes de depositar el material físico dentro de una colección. Aunque las colecciones personales
                        están representados por una muestra física, se clasifican como &quot;observaciones&quot; hasta el
                        el material físico está disponible públicamente dentro de una colección.';
$LANG['MANAGEMENT'] = 'Administración';
$LANG['SNAPSHOT'] = 'Instantánea';
$LANG['LIVE_DATA'] = 'Datos en vivo';
$LANG['AGGREGATE'] = 'Agregado';
$LANG['MORE_INFO_TYPE'] = 'Más información sobre el Tipo de Gestión';
$LANG['SNAPSHOT_DEF'] = 'Usar instantánea cuando haya una base de datos interna separada mantenida en la colección y el conjunto de datos
                        Dentro del portal Symbiota solo hay una instantánea actualizada periódicamente de la base de datos central.
                        Un conjunto de datos en vivo es cuando los datos se administran directamente dentro del portal y la base de datos central son los datos del portal.';
$LANG['GUID_SOURCE'] = 'fuente GUID';
$LANG['NOT_DEFINED'] = 'No definido';
$LANG['MORE_INFO_GUID'] = 'Más información sobre el Identificador Único Global';
$LANG['OCCURRENCE_ID'] = 'Identificador de ocurrencia';
$LANG['SYMB_GUID'] = 'GUID generado por Symbiota (UUID)';
$LANG['OCCID_DEF_1'] = 'El ID de ocurrencia se usa generalmente para
                        Conjuntos de datos de instantáneas cuando un campo de identificador único global (GUID)
                        lo proporciona la base de datos de origen (por ejemplo, especificar la base de datos) y el GUID se asigna al';
$LANG['OCCURRENCEID'] = 'ocurrenciaId';
$LANG['OCCID_DEF_2'] = 'campo. No se recomienda el uso del ID de ocurrencia como GUID para conjuntos de datos en vivo.
                        El número de catálogo se puede utilizar cuando el valor dentro del campo del número de catálogo es globalmente único.
                        La opción GUID generado por Symbiota (UUID) activará el portal de datos de Symbiota para automáticamente
                        generar GUID UUID para cada registro. Esta opción se recomienda para muchos conjuntos de datos en vivo.
                        pero no está permitido para colecciones de instantáneas administradas en el sistema de administración local.';
$LANG['PUBLISH_TO_AGGS'] = 'Publicar en agregadores';
$LANG['MORE_INFO_AGGREGATORS'] = 'Más información sobre la publicación en agregadores';
$LANG['ACTIVATE_GBIF'] = 'Activa las herramientas de publicación GBIF disponibles dentro de la opción de menú Darwin Core Archive Publishing';
$LANG['SOURCE_REC_URL'] = 'URL del registro de origen';
$LANG['DYNAMIC_LINK_REC'] = 'Enlace dinámico a la página de registro individual de la base de datos de origen';
$LANG['MORE_INFO_SOURCE'] = 'Más información sobre la URL de registros fuente';
$LANG['ADVANCE_SETTING'] = 'Configuración avanzada: Agregar un
                        La plantilla de URL aquí insertará un enlace al registro fuente dentro de la página de detalles del espécimen.
                        Se puede incluir un título de URL opcional con dos puntos que delimitan el título y la URL.
                        Por ejemplo, &quot;registro fuente SEINet';
$LANG['ADVANCE_SETTING_2'] = 'mostrará el ID con la URL apuntando al original
                        nado dentro del SEINet. O';
$LANG['ADVANCE_SETTING_3'] = 'puede usarse para una importación de iNaturalist si asignaste su campo ID como fuente
                        o, dbpk) durante la importación. Los patrones de plantilla --CATALOGNUMBER--, --OTHERCATALOGNUMBERS-- y --OCCURRENCEID-- son opciones adicionales.';
$LANG['ICON_URL'] = 'URL del icono';
$LANG['WHAT_ICON'] = '¿Qué es un icono?';
$LANG['UPLOAD_ICON'] = 'Cargue un archivo de imagen de ícono o ingrese la URL de un ícono de imagen que represente la colección. Si ingresa la URL de una imagen ya ubicada
                        quot;Ingresar URL&quot;. La ruta URL puede ser absoluta o relativa. El uso de iconos es opcional.';
$LANG['ENTER_URL'] = 'Ingresar URL';
$LANG['UPLOAD_LOCAL'] = 'Subir imagen local';
$LANG['SORT_SEQUENCE'] = 'Ordenar secuencia';
$LANG['MORE_SORTING'] = 'Más información sobre Clasificación';
$LANG['LEAVE_IF_ALPHABET'] = 'Deje este campo vacío si desea que las colecciones se ordenen alfabéticamente (predeterminado)';
$LANG['COLLECTION_ID'] = 'ID de colección (GUID)';
$LANG['MORE_INFO'] = 'Más información';
$LANG['EXPLAIN_COLLID'] = 'Identificador único global para esta colección (ver';
$LANG['DWC_COLLID'] = 'dwc:collectionID';
$LANG['EXPLAIN_COLLID_2'] = 'Si su colección ya tiene un GUID previamente asignado, ese identificador debe estar representado aquí.
                        Para especímenes físicos, la mejor práctica recomendada es utilizar un identificador de un registro de colecciones como el
                        Registro Global de Repositorios de Biodiversidad';
$LANG['SECURITY_KEY'] = 'Clave de seguridad';
$LANG['RECORDID'] = 'ID de registro';
$LANG['SAVE_EDITS'] = 'Guardar ediciones';
$LANG['CREATE_COLL_2'] = 'Crear nueva colección';
$LANG['TINYMCE_INFO'] = 'Descripción de la colección. Puede acceder a la barra de herramientas de edición de texto para esta descripción presionando 
                        la combinación de teclas ALT + F10 en Windows u OPCIÓN + F10 en MacOS.';

?>