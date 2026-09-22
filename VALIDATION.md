# Estado de validación — 0.2.0-beta2

## Resultado del CI aportado: 96835402304

Las diez combinaciones instalaron el entorno y superaron PHP syntax. Todas
fallaron en Coding standards, con las mismas 20 infracciones y 7 advertencias:

- Espacio después de `function` en funciones anónimas.
- Distribución e indentación de condiciones multilínea.
- Formato de una llamada multilínea a `html_writer::link`.
- Guardas MOODLE_INTERNAL redundantes en cuatro clases y tres archivos de pruebas.

PHPUnit, Behat y las comprobaciones posteriores no se ejecutaron en esa corrida.
La beta2 corrige estos hallazgos; el workflow conserva `--max-warnings 0` y no
usa `continue-on-error`. Tras una instalación exitosa, cada control puede
completarse aunque otro falle, para obtener todos los resultados en una corrida.
La confirmación de que los controles pasan requiere ejecutar el CI actualizado.

El usuario informó que la prueba manual de funcionamiento no mostró errores.
Esto no sustituye las pruebas automatizadas pendientes.

## Realizado durante la preparación

- Revisión estática del paquete original y de las modificaciones.
- Análisis sintáctico de todos los archivos PHP mediante un parser PHP y lint
  en runtimes PHP 7.4 y 8.4 aislados (WebAssembly).
- 21 comprobaciones ejecutadas de CSV y validación, correctas en PHP 7.4.33
  y 8.4.25. Se ejecutó el código del plugin con sustitutos mínimos de funciones
  de Moodle; no equivalen a ejecutar PHPUnit ni la integración con Moodle.
- Lectura estructural del workflow YAML y revisión de la matriz.
- Comprobación de claves de idioma EN/ES y referencias de cadenas.
- Generación de archivos XLS y XLSX auténticos para las pruebas de equivalencia.
- Verificación del contenido y estructura del ZIP de entrega.

## Pruebas incluidas que debe ejecutar Moodle/GitHub

- PHPUnit: lectores y equivalencia de formatos; CSV con BOM, delimitadores y
  descripciones multilínea; encabezados y puntuaciones inválidas; reglas de
  calificación; fórmulas, archivos falsos y celdas combinadas; límites; aislamiento
  del área de borradores; vista previa sin escritura; guardado nativo; prevención
  de importación repetida; tareas y foros; permisos y grupos.
- Behat: CSV/XLS/XLSX desde carga hasta editor nativo; cancelación; rechazo de
  fórmulas y acceso directo de estudiantes.
- Análisis de código, documentación y validación del plugin en CI.

## Pendiente antes de publicar como estable

1. Ejecutar la matriz completa de GitHub Actions y corregir cualquier fallo.
2. Verificar instalación nueva y actualización desde 0.1.1 en Moodle real.
3. Importar las plantillas, guardar la rúbrica como lista y calificar a un
   estudiante en una tarea y en la calificación global de un foro.
4. Comprobar manualmente el vencimiento de vista previa, doble envío y un fallo
   inducido durante la escritura para verificar reversión completa.
5. Contrastar una plantilla real de la versión actual de la extensión Chrome.
   Se conserva el contrato de cuatro columnas recuperado, pero no se recibió
   el código ni un archivo actual de esa extensión en esta sesión.
6. Ejecutar nuevamente contra la beta/RC actual de Moodle 5.3 y cambiar `main`
   por `MOODLE_503_STABLE` cuando exista esa rama.
7. Confirmar plazo Early bird y publicar el paquete validado en Moodle.

El CI proporcionado sí ejecutó la instalación y lint en sus diez entornos.
Localmente no se ejecutó un servidor Moodle, PHPUnit ni Behat. Los resultados de análisis estático no sustituyen estas pruebas.
No se ha publicado ni concedido ninguna insignia.

Las notas oficiales de requisitos de base de datos de 5.3 presentaban diferencias
entre la página de versión y la de descargas. La matriz usa MariaDB 11.4 y
PostgreSQL 17, y deberá contrastarse con el environment.xml de la beta utilizada.
