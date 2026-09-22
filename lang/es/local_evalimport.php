<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_evalimport.
 *
 * @package    local_evalimport
 * @category   string
 * @copyright  2026 Richard Rangel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activitynotvisible'] = 'No tienes acceso a esta actividad para importar rúbricas.';
$string['choosecsvfile'] = 'Archivo CSV de rúbrica';
$string['choosefile'] = 'Archivo de rúbrica (CSV, XLS o XLSX)';
$string['column_criterion'] = 'Criterio';
$string['column_level'] = 'Nivel';
$string['column_level_description'] = 'Descripción';
$string['column_score'] = 'Puntuación';
$string['confirmimport'] = 'Confirmar importación';
$string['continueimporting'] = 'Continuar importando';
$string['csvmissingcolumns'] = 'Utiliza exactamente estos cuatro encabezados sin repetirlos: criterion, level, level_description, score.';
$string['csvrequired'] = 'Debes cargar un archivo CSV.';
$string['downloadtemplate'] = 'Descargar plantilla {$a}';
$string['emptycell'] = 'Falta un valor obligatorio.';
$string['emptyrubric'] = 'El archivo debe contener encabezados y al menos un criterio.';
$string['enablemaxlevelscore'] = 'Activar límite máximo de puntos por nivel';
$string['enablemaxlevelscore_desc'] = 'Ningún nivel podrá superar el valor configurado.';
$string['enableminlevelscore'] = 'Exigir un nivel con la puntuación mínima';
$string['enableminlevelscore_desc'] = 'Cada criterio debe incluir un nivel con exactamente la puntuación configurada.';
$string['errormaxexceeded'] = 'En el criterio "{$a->criterion}", la puntuación {$a->score} supera el máximo permitido ({$a->max}).';
$string['errorminmissing'] = 'En el criterio "{$a->criterion}" falta un nivel con la puntuación mínima requerida ({$a->min}).';
$string['errormismatchtotal'] = 'La suma de los máximos de la rúbrica ({$a->sum}) debe coincidir con la calificación máxima de la actividad ({$a->grademax}).';
$string['errorrepeatedscores'] = 'Hay puntuaciones repetidas en el criterio "{$a}".';
$string['errorrubricexists'] = 'Esta actividad ya tiene una rúbrica. Revísala en el editor de Moodle.';
$string['evalimport:view'] = 'Acceder al importador de instrumentos de evaluación';
$string['filehelp'] = 'Utiliza criterion, level, level_description y score como encabezados. Repite el criterio en cada fila de nivel. En Excel se lee la hoja Rubrica, o la primera si no existe. Límites: 2 MB y 2.000 filas de datos. Las plantillas suman 20 puntos; adáptalas a la calificación máxima de la actividad.';
$string['filerequired'] = 'Carga exactamente un archivo de rúbrica.';
$string['formulanotallowed'] = 'La celda {$a} contiene una fórmula o un error de Excel. Sustitúyelo por un valor.';
$string['fourcolumns'] = 'Cada fila con datos debe contener exactamente cuatro celdas.';
$string['gotoadvancedgrading'] = 'Ir a calificación avanzada';
$string['importbusy'] = 'Hay otra importación en curso para esta actividad. Inténtalo nuevamente en unos segundos.';
$string['importedrubricname'] = 'Rúbrica importada {$a}';
$string['importfailed'] = 'No se pudo completar la importación. Si el problema persiste, contacta al administrador.';
$string['importinstrument'] = 'Importar instrumento de evaluación';
$string['importrubric'] = 'Importar';
$string['importsuccess'] = 'La rúbrica se importó como borrador. Revísala y guárdala como lista para usar en el editor de Moodle.';
$string['invalidcell'] = 'La celda {$a} contiene una fecha o un valor booleano en lugar del dato requerido.';
$string['invalidcsvencoding'] = 'Guarda el CSV en UTF-8 y comprueba que no supere 2 MB.';
$string['invalidfilesize'] = 'El archivo está vacío o supera los límites de importación (2 MB; 20 MB descomprimidos para XLSX).';
$string['invalidfiletype'] = 'Utiliza un archivo CSV, XLS o XLSX con su extensión original.';
$string['invalidgrading'] = 'La actividad debe tener una calificación máxima numérica positiva. En foros, activa la calificación global.';
$string['invalidscore'] = 'Utiliza un número no negativo, con hasta cinco decimales y sin separadores de miles.';
$string['invalidtext'] = 'El texto contiene un carácter inválido o supera 10.000 caracteres.';
$string['invalidworkbook'] = 'No se pudo leer el libro. Comprueba el formato y elimina el cifrado con contraseña.';
$string['maxlevelscore'] = 'Puntuación máxima por nivel';
$string['maxlevelscore_desc'] = 'Máxima puntuación permitida en un nivel.';
$string['mergedcells'] = 'Utiliza una hoja sin celdas combinadas.';
$string['minlevelscore'] = 'Puntuación mínima requerida';
$string['minlevelscore_desc'] = 'Cada criterio debe incluir un nivel con esta puntuación.';
$string['noactivitiesavailable'] = 'No hay actividades compatibles disponibles. Configura una calificación numérica en la tarea o en la calificación global del foro y revisa tus permisos.';
$string['nogradingarea'] = 'No se encontró un área compatible de calificación avanzada.';
$string['notenoughlevels'] = 'El criterio "{$a}" debe contener al menos dos niveles.';
$string['othergradingmethod'] = 'Hay otro método de calificación avanzada activo. Revísalo en Moodle antes de cambiar a una rúbrica.';
$string['pageheading'] = 'Importar instrumento de evaluación';
$string['pluginname'] = 'Importador de instrumentos de evaluación';
$string['previewexpired'] = 'La vista previa venció o el archivo cambió. Carga el archivo y vuelve a revisarlo.';
$string['previewimport'] = 'Vista previa de la rúbrica';
$string['previewnotice'] = 'Aún no se ha modificado la calificación. Confirma para crear un borrador. Los nombres de nivel son orientativos; Moodle guarda sus descripciones y puntuaciones. Los niveles se muestran de mayor a menor puntuación.';
$string['privacy:metadata'] = 'El plugin no tiene un almacén propio de datos personales. Los archivos utilizan el área de borradores de Moodle; las rúbricas y sus autores se gestionan mediante el subsistema de calificación. Las referencias temporales de confirmación se guardan en la sesión.';
$string['rowerror'] = 'Fila {$a->row}, columna {$a->column}: {$a->reason}';
$string['sample_argument'] = 'Argumentación';
$string['sample_argument_competent'] = 'Presenta argumentos con algunas debilidades.';
$string['sample_argument_developing'] = 'No fundamenta sus argumentos.';
$string['sample_argument_excellent'] = 'Presenta argumentos claros y fundamentados.';
$string['sample_competent'] = 'Competente';
$string['sample_developing'] = 'En desarrollo';
$string['sample_excellent'] = 'Excelente';
$string['sample_structure'] = 'Organización';
$string['sample_structure_competent'] = 'Organiza las ideas con algunas dificultades.';
$string['sample_structure_developing'] = 'No presenta una organización clara.';
$string['sample_structure_excellent'] = 'Organiza las ideas con claridad y coherencia.';
$string['selectactivity'] = 'Actividad de evaluación';
$string['toomanyrows'] = 'El archivo supera las 2.000 filas de datos.';
$string['workbookdimensions'] = 'Utiliza hasta 2.000 filas de datos y cuatro columnas. Elimina filas o columnas vacías que tengan formato.';
