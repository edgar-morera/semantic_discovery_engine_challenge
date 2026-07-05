# Plantilla: agente de revisión de código

Cómo construir el prompt de un agente revisor para cualquier proyecto y cualquier IA (Claude, Copilot, Cursor, un bot de CI que llama a un LLM por API…). El prompt resultante es texto plano: lo que cambia según la herramienta es solo dónde lo pegas.

El agente se compone de una ficha de metadatos (bloque 0) y un prompt de 8 bloques. Para cada uno: la plantilla a rellenar y un ejemplo real (este proyecto).

---

## Bloque 0 — Ficha del agente (metadatos)

Antes del prompt, todo agente necesita cuatro datos que la herramienta usa para registrarlo, decidir cuándo invocarlo y con qué capacidades. La sintaxis varía por herramienta (frontmatter YAML, config JSON, parámetros de API), pero los campos son siempre estos:

```text
nombre:       <identificador corto, p. ej. code-reviewer>
descripción:  <CONDICIÓN DE DISPARO: qué revisa + cuándo usarlo + qué no hace.
               La lee el orquestador para decidir delegar — escríbela como "Usar cuando...">
capacidades:  <solo lectura + ejecución de comandos. NUNCA escritura>
modelo:       <el más capaz disponible en la herramienta>
```

**Qué especificar:**
- **descripción**: es el campo más importante — en herramientas con delegación automática decide si el agente se usa o no. Debe contener la condición de disparo ("usar cuando se pida revisar un diff/rama/archivo") y la restricción visible ("solo informa, nunca modifica"), no una descripción de marketing.
- **capacidades**: la lista concreta depende de la herramienta; el criterio no: lectura de archivos, búsqueda y ejecución de comandos (para las herramientas de verificación del bloque 6), sin escritura.
- **modelo**: revisar código es la tarea donde más diferencia hay entre modelos; aquí no se ahorra. Fíjalo explícitamente para que el coste no dependa de la sesión que invoque al agente.

**Ejemplo (sintaxis de Claude Code — frontmatter YAML):**

```yaml
---
name: code-reviewer
description: Revisa código PHP de este proyecto contra sus convenciones (hexagonal + DDD + CQRS, Deptrac, strict_types, excepciones de dominio). Usar cuando se pida revisar un diff, una rama, un archivo o cambios sin commitear. Solo informa — nunca modifica código.
tools: Read, Grep, Glob, Bash
model: opus
---
```

En Cursor el equivalente es la cabecera de la regla (description + globs); en un bot de CI, la configuración del job (modelo del API call, permisos del token); en Copilot no hay delegación automática, así que solo aplican capacidades y modelo si la plataforma los expone.

---

## Bloque 1 — Identidad y alcance

Define en 2-3 frases qué es el agente, qué revisa por defecto y la restricción clave: **informa, no modifica**.

```text
Eres un revisor de código senior para <proyecto: stack, arquitectura, dominio en una frase>.
Revisas el código que se te indique (por defecto: <entrada por defecto, p. ej. git diff contra la rama principal>).
No modificas nada: tu salida es un informe de hallazgos.
No sugieras refactors fuera del alcance del código revisado.
```

**Qué especificar:** el stack y la arquitectura (condicionan qué reglas aplican), la entrada por defecto y el límite de alcance. Sin la última línea, cada revisión deriva en una lista de deseos sobre todo el proyecto.

**Ejemplo:**
> Eres un revisor de código senior para un proyecto Symfony con Arquitectura Hexagonal + DDD + CQRS (buscador semántico de productos con Qdrant, Redis y HuggingFace). Revisas por defecto `git diff main...HEAD` más los cambios sin commitear. No modificas nada. No sugieras refactors fuera del alcance del diff.

---

## Bloque 2 — Reglas de arquitectura, con severidad

Lista las reglas estructurales del proyecto como afirmaciones **verificables archivo a archivo**, cada una con su severidad. Nada de "sigue buenas prácticas": eso produce ruido genérico.

```text
Arquitectura (violación = bloqueante):
- <regla verificable 1>
- <regla verificable 2>
- ...
```

**Qué especificar:** de dónde salen las reglas — configuración de arquitectura si existe (Deptrac, ArchUnit, dependency-cruiser), y si no, las reglas de capas/módulos que el equipo aplica de palabra. Escríbelas de forma que se pueda comprobar mirando imports y firmas.

**Ejemplo:**
> - Domain no depende de nada interno ni de framework (cero `use Symfony\...` o `Doctrine\...` en `src/Product/Domain/`).
> - Application solo depende de Domain; Infrastructure de Domain y Application.
> - Los puertos (interfaces) viven en Domain; los adaptadores en Infrastructure.
> - Los command handlers no devuelven datos; las queries no mutan estado.

---

## Bloque 3 — Decisiones cerradas

Las decisiones de diseño no obvias que el equipo ya tomó. Márcalas explícitamente como cerradas: sin esa marca, la IA tenderá a "corregir" justo lo que se decidió a propósito.

```text
Decisiones cerradas (no las cuestiones, hazlas cumplir; señala cualquier cambio que las revierta):
- <decisión no obvia 1 + qué diff la revertiría>
- ...
```

**Qué especificar:** todo aquello que un desarrollador nuevo preguntaría "¿y esto por qué es así?". Fuente típica: los ADRs si existen; si no, estas decisiones suelen vivir solo como conocimiento tácito de las personas del equipo — escribir este bloque es una buena excusa para documentarlas por fin como ADRs.

**Ejemplo:**
> - El VO `Embedding` no forma parte del agregado `Product` ni se persiste en MySQL. Señala cualquier diff que lo añada como propiedad del agregado.
> - El UUID se genera en el controlador antes de despachar el comando, para que el command handler no devuelva datos. No "simplifiques" esto devolviendo el ID desde el handler.

---

## Bloque 4 — Convenciones de código

Las normas del equipo que las herramientas automáticas no cubren (las que sí cubren, van al bloque 6 como comandos).

```text
Convenciones (violación = importante):
- <convención 1>
- ...
- Todo cambio de comportamiento debe traer test que lo cubra.
```

**Ejemplo:**
> - `declare(strict_types=1)` en todo archivo PHP.
> - Solo excepciones de dominio — nunca `\Exception` genéricas desde Domain o Application.
> - Value Objects inmutables que validan en el constructor.
> - Tests: clases `<ClaseProbada>Test.php`, métodos en `snake_case`.

---

## Bloque 5 — Checklist de seguridad

Acotado a los riesgos reales del stack — un checklist de 4-6 puntos que se aplica en toda revisión. Genérico infinito = revisiones superficiales.

```text
Seguridad (violación = bloqueante o importante según impacto):
- <riesgo 1 concreto del stack>
- ...
Si el diff toca superficie sensible (<definirla: p. ej. controladores, SQL, manejo de secretos>),
haz una segunda pasada dedicada solo a seguridad sobre esos archivos.
```

**Qué especificar:** los riesgos según el stack (web con SQL → inyección; APIs → validación de entrada y fugas en errores; cualquier proyecto → secretos). La "segunda pasada" es la versión agnóstica del escalado: si tu herramienta tiene un analizador de seguridad dedicado (comando, skill, job de CI), sustituye la frase por su invocación; si no, la segunda pasada del propio agente es el mínimo válido.

**Ejemplo:**
> - SQL vía DBAL siempre con parámetros vinculados — nunca concatenación de entrada del usuario.
> - Ningún secreto hardcodeado ni volcado en logs (la `HUGGINGFACE_API_KEY` no debe aparecer en errores del cliente HTTP).
> - Entrada de controladores validada: errores de dominio → 400 controlado, nunca 500 con stack trace.
> - Respuestas de error sin detalles internos (rutas, clases, SQL).

---

## Bloque 6 — Comandos de verificación

Los comandos **exactos** del proyecto que el agente debe ejecutar, cuándo es obligatorio cada uno, y la regla de citar la salida literal. (Si la herramienta de destino no puede ejecutar comandos, elimina este bloque y haz que el CI aporte estas salidas como contexto.)

```text
Ejecuta según lo que toque el diff:
- <comando 1>   # <qué verifica> — <cuándo es obligatorio>
- ...
Si una herramienta falla, incluye su salida relevante literal en el informe; no la parafrasees.
```

**Ejemplo:**
> - `make stan` — análisis estático nivel 8
> - `make deptrac` — reglas de capas; obligatorio SIEMPRE que el diff toque `src/`
> - `make cs` — estilo (dry-run)
> - `make test` — PHPUnit

---

## Bloque 7 — Formato de salida

Fija la estructura del informe para que las revisiones sean comparables entre sí y procesables.

```text
Formato del informe:
1. Veredicto en una línea: aprobado / aprobado con observaciones / cambios necesarios.
2. Hallazgos ordenados por severidad (bloqueante / importante / menor),
   cada uno con archivo:línea, el problema y la corrección sugerida.
3. Salida literal de las herramientas que hayan fallado.
```

---

## Bloque 8 — Qué NO reportar

Tan importante como lo anterior: sin esto, el informe se llena de ruido.

```text
No reportes:
- Preferencias de estilo que <formateador del proyecto> ya cubre.
- Refactors fuera del alcance del diff.
- <otras exclusiones del equipo>
```

---

## Montaje según la herramienta

Concatena los 8 bloques rellenos: ese texto ES el agente. Dónde va:

| Herramienta | Dónde pegarlo |
|---|---|
| Claude Code | Cuerpo de `.claude/agents/code-reviewer.md`; el bloque 0 va como frontmatter YAML |
| GitHub Copilot | `.github/copilot-instructions.md` o instrucciones de code review del repo |
| Cursor | Regla en `.cursor/rules/` |
| Bot de CI por API | Prompt de sistema; el diff del PR como mensaje de usuario |

Dos ajustes transversales al portar:
- **Permisos:** si la herramienta permite restringir capacidades, dale solo lectura + ejecución (nunca escritura: un revisor que edita deja de ser revisor).
- **Modelo:** si se puede elegir, usa el más capaz disponible — la detección de bugs sutiles es donde más diferencia hay entre modelos.

## Checklist de calidad del prompt terminado

- [ ] La descripción de la ficha es una condición de disparo ("usar cuando..."), no marketing.
- [ ] Cada regla es verificable mirando código (no "sigue buenas prácticas").
- [ ] Cada regla tiene severidad.
- [ ] Las decisiones cerradas están marcadas como tales.
- [ ] Los comandos existen realmente en el proyecto (ejecútalos antes de escribirlos).
- [ ] Hay formato de salida fijo y lista de exclusiones.
- [ ] El prompt cabe en 1-2 páginas: si es más largo, sobra teoría; si es más corto, falta proyecto.
