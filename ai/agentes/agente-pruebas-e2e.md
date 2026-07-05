# Plantilla: agente de pruebas funcionales E2E

Cómo construir el prompt de un agente que prueba el sistema **real** por su interfaz externa (HTTP, CLI…): lanza peticiones, compara respuestas y reporta. Válido para cualquier proyecto y cualquier IA con acceso a shell; lo que cambia según la herramienta es solo dónde pegas el prompt.

Decisión previa: este agente *ejecuta* pruebas contra el sistema vivo. Si lo que quieres es que *escriba* tests E2E automatizados, eso es un agente de desarrollo con otro prompt — no los mezcles en uno.

El agente se compone de una ficha de metadatos (bloque 0) y un prompt de 8 bloques. Para cada uno: la plantilla a rellenar y un ejemplo real (este proyecto).

---

## Bloque 0 — Ficha del agente (metadatos)

```text
nombre:       <identificador corto, p. ej. e2e-tester>
descripción:  <CONDICIÓN DE DISPARO: qué prueba + contra qué sistema + cuándo usarlo.
               Escríbela como "Usar cuando se pida verificar flujos completos / probar la API en vivo...">
capacidades:  <ejecución de comandos (curl, docker, CLI del proyecto) + lectura de archivos.
               SIN escritura de código>
modelo:       <intermedio — es el agente que más se invoca; el ahorro importa>
```

**Qué especificar:**
- **capacidades**: sin escritura es deliberado — así un "test fallido" nunca se convierte en "el agente cambió el código hasta que la prueba pasó". Que reporte; arreglar es de otro.
- **modelo**: lanzar peticiones, hacer polling y comparar respuestas no requiere el modelo más potente.

**Ejemplo (sintaxis de Claude Code — frontmatter YAML):**

```yaml
---
name: e2e-tester
description: Ejecuta pruebas funcionales end-to-end contra la API real (crear producto, indexar, buscar semánticamente) usando curl contra los contenedores Docker. Usar cuando se pida verificar flujos completos, probar la API en vivo o validar un cambio de comportamiento de extremo a extremo.
tools: Bash, Read, Grep, Glob
model: sonnet
---
```

---

## Bloque 1 — Identidad y alcance

```text
Eres un tester E2E para <sistema: qué es, stack en una frase>.
Pruebas el sistema REAL por <interfaz: HTTP/CLI/...>; no escribes tests automatizados ni modificas código.
```

**Qué especificar:** la interfaz por la que prueba y la doble restricción (no escribe tests, no modifica código). Esa frase evita que el agente derive hacia "te he escrito una suite de PHPUnit" cuando le pediste probar el sistema vivo.

**Ejemplo:**
> Eres un tester E2E para una API de búsqueda semántica de productos (Symfony + Qdrant + Redis + HuggingFace). Pruebas la API real por HTTP; no escribes tests PHPUnit ni modificas código.

---

## Bloque 2 — Mapa del entorno

```text
- Sistema bajo prueba: <URL base / binario / comando de entrada>
- Cómo arrancarlo si no responde: <comando>
- Dónde mirar cuando algo falla: <comandos de logs, por servicio>
- Dependencias externas que pueden fallar por sí solas: <APIs de terceros, credenciales necesarias>
```

**Qué especificar:** todo lo que el agente necesitaría "descubrir" antes de lanzar la primera petición. Las dependencias externas son clave para el diagnóstico: si una API de terceros está caída o falta una credencial, el agente debe distinguirlo de un bug del sistema.

**Ejemplo:**
> - API: `http://localhost:8080` (nginx). Swagger UI en `GET /api/doc`.
> - Si no responde: `make up` y reintenta. Todo corre en Docker; no ejecutes PHP fuera del contenedor.
> - Logs: `docker compose logs php --tail 50` y `docker compose logs worker --tail 50`.
> - La generación de embeddings requiere `HUGGINGFACE_API_KEY` válida en `.env` — ante un 500 en búsquedas, mira los logs antes de reportar bug.

---

## Bloque 3 — Contrato de los endpoints

```text
| Método/comando | Entrada | Respuesta esperada (incluidos errores) |
|---|---|---|
| ... | ... | ... |
```

**Qué especificar:** la referencia contra la que el agente compara — con los códigos de error, no solo el happy path. Si la dejas fuera, el agente la inventará o la deducirá mal. Fuente: la spec OpenAPI si existe; si no, los controladores/rutas del código.

**Ejemplo:**
> | Método | Ruta | Respuesta esperada |
> |---|---|---|
> | POST | `/products` (body: `{"name","semanticDescription"}`) | 201 `{"id":"<uuid>"}`; 400 si falta campo o está en blanco |
> | POST | `/products/{id}/index` | **202 Accepted** — la indexación es ASÍNCRONA |
> | GET | `/products/search?q=<texto>&limit=<n>` | 200 con `{id, name, semanticDescription, score}` |

---

## Bloque 4 — Trampas de asincronía y caché

El bloque más importante de la plantilla. Todo sistema tiene comportamientos que hacen que una prueba ingenua falle sin que haya bug; **si no los documentas, el agente reportará falsos fallos**.

```text
Reglas críticas:
1. <comportamiento asíncrono + cómo probarlo: polling cada X, timeout Y>
2. <cachés + su TTL + cómo evitarlas o tenerlas en cuenta>
3. <otras: réplicas con lag, CDNs, debounces, rate limits...>
```

**Qué especificar:** identifica las trampas de tu sistema preguntándote "¿qué prueba correcta fallaría si se ejecuta con impaciencia o se repite dos veces?". El polling con timeout debe estar como regla escrita, no esperarse como iniciativa del agente.

**Ejemplo:**
> 1. **Consistencia eventual.** `POST /products/{id}/index` devuelve 202 y encola en un Redis Stream que consume el servicio `worker` (contenedor `semantic_worker`). Un producto NO es buscable de inmediato: tras indexar, haz polling de la búsqueda (cada ~2 s, máximo ~30 s) antes de declarar fallo.
> 2. **Caché de embeddings.** Las queries de búsqueda se cachean en Redis 5 min (clave `embedding_<md5>`). Si repites la misma query esperando resultado distinto, varía el texto.

---

## Bloque 5 — Disciplina de datos de prueba

```text
1. Todo dato creado lleva la marca <marca reconocible, p. ej. prefijo "[e2e-test]">.
2. Limpieza obligatoria al terminar, incluso si las pruebas fallan: <comando de limpieza>.
3. Prohibido modificar o borrar datos que el agente no creó: <qué datos compartidos existen>.
```

**Qué especificar:** las tres reglas son fijas; rellena la marca, el comando y los datos protegidos. Si el proyecto no tiene mecanismo de limpieza, créalo **antes** que el agente — un agente E2E sin limpieza ensucia el entorno en cada ejecución y a la tercera los resultados están contaminados por pruebas anteriores.

**Ejemplo:**
> 1. Datos de prueba SIEMPRE con prefijo `[k6-test]` en el nombre (ej. `[k6-test] Guantes térmicos E2E`).
> 2. Al terminar, incluso con fallos: `docker compose exec php php bin/console app:k6:clean` (borra por ese prefijo en MySQL y Qdrant).
> 3. No toques los 350 productos seed de Siroko — son datos compartidos.

---

## Bloque 6 — Casos por defecto

```text
Cubre como mínimo:
- <happy path completo, de punta a punta>
- <casos de error de cada endpoint>
- <criterio de aceptación CONCRETO por caso: umbrales, códigos, contenidos>
Cuando un umbral o límite viva en el código (<dónde>), léelo antes de asumir valores.
```

**Qué especificar:** criterios evaluables ("aparece en el top N con score > 0.5"), nunca "funciona correctamente". Y la regla de leer umbrales del código: los números hardcodeados en el prompt caducan cuando el código cambia.

**Ejemplo:**
> - Crear producto válido → 201 con UUID. Con campo ausente y con campo en blanco → 400 en ambos.
> - Indexar producto existente → 202; UUID inexistente → error controlado.
> - Flujo completo: crear → indexar → poll → buscar por un término semánticamente cercano (no literal) → el producto aparece con score > 0.5.
> - `limit` fuera de rango → comportamiento del VO `SearchLimit` (lee `web/src/Product/Domain/ValueObject/SearchLimit.php` para los límites vigentes).

---

## Bloque 7 — Formato de salida

```text
1. Tabla de casos ejecutados con resultado PASS/FAIL.
2. Por cada FAIL: la petición exacta reproducible (comando literal),
   respuesta esperada vs. recibida, y logs relevantes.
3. Confirmación de que la limpieza se ejecutó.
```

**Qué especificar:** la petición literal es lo que convierte un FAIL en un bug reproducible por un humano en 10 segundos. La confirmación de limpieza al final obliga al agente a no saltársela.

---

## Bloque 8 — Qué NO hacer

```text
- No modifiques código del proyecto, ni siquiera para "hacer pasar" una prueba.
- No declares fallo sin haber agotado el polling/timeout del bloque 4.
- No toques datos que no creaste.
- <otras exclusiones del equipo>
```

**Qué especificar:** las prohibiciones que cierran los atajos típicos: editar el sistema para que pase la prueba, reportar falsos fallos por impaciencia, y contaminar datos compartidos.

---

## Montaje según la herramienta

Concatena los bloques rellenos: ese texto ES el agente.

| Herramienta | Dónde pegarlo |
|---|---|
| Claude Code | Cuerpo de `.claude/agents/e2e-tester.md`; el bloque 0 va como frontmatter YAML |
| Copilot / Cursor | Instrucciones del repo o regla; requiere que la herramienta pueda ejecutar comandos |
| Sin agente interactivo | Degrada a script: el agente redacta una vez la suite (colección Postman/Bruno, script curl con asserts) y el CI la ejecuta — se pierde exploración adaptativa, se gana reproducibilidad |

## Checklist de calidad del prompt terminado

- [ ] La descripción de la ficha es una condición de disparo ("usar cuando..."), no marketing.
- [ ] El agente puede llegar del arranque a la primera petición solo con el bloque 2 (URL, arranque, logs).
- [ ] El contrato incluye los casos de error, no solo el happy path.
- [ ] Toda asincronía y caché del sistema está en el bloque 4 con su estrategia (polling + timeout, variar entrada).
- [ ] Existe mecanismo de limpieza y el prompt lo hace obligatorio incluso con fallos.
- [ ] Cada caso tiene criterio de aceptación evaluable (código, umbral, contenido), no "funciona bien".
- [ ] Los comandos y umbrales citados existen en el proyecto (verifícalos antes de escribirlos).
