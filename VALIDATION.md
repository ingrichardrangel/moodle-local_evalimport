# Estado de validación — 0.2.0-beta4

## Resultado del CI aportado: 96931327496

Las diez combinaciones ejecutaron los 30 tests. En cada una pasaron 29 y falló
únicamente la primera aserción del escenario de grupos. El lector CSV/XLS/XLSX,
las plantillas, la validación y la persistencia nativa pasaron en Moodle
4.1–5.3 beta con PHP 7.4–8.4 y MariaDB/PostgreSQL.

CodeSniffer encontró una llamada multilínea, dos nombres de métodos heredados y
la pérdida de asociación entre los docblocks de cobertura y las clases de
prueba. La beta4 corrige esos hallazgos y hace explícita la configuración de
grupos/permisos de la prueba restante. Se requiere una nueva ejecución para
confirmar la matriz completa.

Los avisos del runner por `actions/checkout@v4` y `actions/setup-node@v4` se
atienden actualizando ambas acciones a v5. Los mensajes periódicos de salud de
los servicios PostgreSQL/MariaDB no causaron fallos de pasos.

## Resultado del CI aportado: 96845583544

Las diez combinaciones fallan en PHPUnit. Los fallos identificados son:

- XLS: los componentes XLS/OLE no se distribuyen con el PhpSpreadsheet de Moodle.
- XLSX en 4.1–4.4: faltaba cargar el autoloader mediante excellib.
- Guardado nativo: la prueba consultaba una propiedad areaid inexistente;
  ahora utiliza get_areaid() del controlador.
- Grupos: la prueba usaba un rol vacío; ahora utiliza docente con edición,
  controla accessallgroups y refresca la caché tras cambiar la membresía.
- PHPUnit 11: anotaciones de cobertura obsoletas; se agregaron atributos.

La beta3 corrige estos puntos y mantiene obligatorias las comprobaciones del CI.
No se declara aún que PHPUnit o Behat pasen: requieren una nueva ejecución
con Moodle y base de datos.

## Comprobaciones locales de beta3

- Lint de los 22 archivos PHP en PHP 7.4.33 y 8.4.25: correcto.
- Lectura XLS equivalente a CSV, selección de Rubrica y valores numéricos
  con formato: correctos en ambos runtimes.
- Fórmulas, celdas combinadas, booleanos y cadenas OLE cíclicas: rechazados.
- Plantillas XLS EN/ES: importables como rúbricas de 20 puntos.
- Entrada pública del lector y limpieza de archivos temporales: correctas.
- Estas comprobaciones usan runtimes WebAssembly y sustitutos mínimos de
  Moodle. No equivalen a PHPUnit de integración ni a Behat.

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

El CI proporcionado ejecutó PHPUnit en sus diez entornos, con los fallos descritos.
Localmente no se ejecutó un servidor Moodle, PHPUnit ni Behat. Los resultados de análisis estático no sustituyen estas pruebas.
No se ha publicado ni concedido ninguna insignia.

Las notas oficiales de requisitos de base de datos de 5.3 presentaban diferencias
entre la página de versión y la de descargas. La matriz usa MariaDB 11.4 y
PostgreSQL 17, y deberá contrastarse con el environment.xml de la beta utilizada.
