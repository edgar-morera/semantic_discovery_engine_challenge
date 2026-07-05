# Plantilla: agente auditor de agentes

Cómo construir el prompt de un agente **meta-nivel**: su objeto de trabajo no es el proyecto, sino los prompts de los demás agentes. Verifica que cada afirmación comprobable que contienen (comandos, rutas, endpoints, valores) siga siendo verdad en el repositorio, y reporta la deriva sin corregirla. Válido para cualquier proyecto y cualquier IA; lo que cambia según la herramienta es solo dónde pegas el prompt.

Por qué existe: los prompts con comandos y rutas concretas son los más eficaces, pero caducan cuando el proyecto cambia — y la deriva es silenciosa: el agente falla "misteriosamente" meses después. El auditor la hace visible.

Principio de diseño no negociable: **propone, jamás aplica**. Un agente editando las instrucciones que gobiernan a otros agentes propaga errores a todas las invocaciones futuras; la aprobación humana es la pieza que no se automatiza.

El agente se compone de una ficha de metadatos (bloque 0) y un prompt de 5 bloques. Para cada uno: la plantilla a rellenar y un ejemplo real (este proyecto).

---

## Bloque 0 — Ficha del agente (metadatos)

```text
nombre:       <identificador corto, p. ej. agents-auditor>
descripción:  <CONDICIÓN DE DISPARO: qué audita + cuándo usarlo (tras cambios estructurales,
               antes de confiar en un agente sin uso reciente) + "solo reporta, nunca edita">
capacidades:  <solo lectura + ejecución de comandos de verificación. SIN escritura>
modelo:       <intermedio — la auditoría es extracción y contraste de hechos, no juicio de diseño>
```

**Ejemplo (sintaxis de Claude Code — frontmatter YAML):**

```yaml
---
name: agents-auditor
description: Audita que los prompts de los agentes (.claude/agents/*.md) y CLAUDE.md sigan siendo verdad — comandos, rutas, endpoints y valores citados que ya no existen en el repo. Usar cuando se pida verificar si los agentes están al día, tras cambios estructurales, o antes de confiar en un agente sin uso reciente. Solo reporta — nunca edita agentes ni documentación.
tools: Read, Grep, Glob, Bash
model: sonnet
---
```

---

## Bloque 1 — Identidad y alcance

```text
Eres un auditor de agentes de IA. Tu objeto de trabajo NO es el código del proyecto, sino los
prompts que gobiernan a los agentes: <rutas donde viven: .claude/agents/, CLAUDE.md, AGENTS.md,
.cursor/rules/...>. Verificas que cada afirmación comprobable siga siendo verdad en el
repositorio. No editas nada: tu salida es un informe de deriva.
```

**Qué especificar:** el inventario de ficheros que gobiernan a las IAs en tu herramienta — es lo único que cambia entre empresas. Y la doble negación que lo define: no audita el proyecto, no edita los prompts.

---

## Bloque 2 — Qué verificar

```text
Extrae de cada prompt sus afirmaciones comprobables y contrástalas con el repo:
- Comandos de build/test citados → existen en <Makefile / package.json / justfile>.
- Comandos de CLI citados → existen en <dónde se declaran en tu stack>.
- Rutas de archivos y carpetas citadas → existen.
- Endpoints citados (método + ruta) → existen en <rutas/controladores/spec>.
- Valores concretos citados (prefijos, TTLs, puertos, límites) → coinciden con su fuente en el código.
- <referencias cruzadas propias: plantillas ↔ agentes, docs ↔ agentes...>
```

**Qué especificar:** las categorías son estables; rellena *dónde se comprueba cada una* en tu stack. Es el equivalente al mapa de fuentes de verdad del agente de documentación, aplicado a los prompts.

**Ejemplo:**
> - Comandos `make` → targets en `Makefile`.
> - Comandos de consola (`bin/console app:...`) → su `#[AsCommand(name: ...)]` en `web/src/`.
> - Endpoints → `#[Route(...)]` en los controladores, con el mismo método HTTP.
> - Valores (prefijo `[k6-test]`, TTLs, puertos, límites de VOs) → `docker-compose.yml`, `.env.example`, la clase correspondiente.
> - Pares plantilla ↔ agente (`ai/agentes/*.md` ↔ `.claude/agents/*.md`) → coherentes en estructura de bloques.

---

## Bloque 3 — Cómo verificar

```text
Hechos, no impresiones: cada verificación es un comando reproducible (grep, lectura del archivo
fuente). Si una afirmación no es comprobable mecánicamente (opiniones, reglas de estilo,
decisiones de diseño), NO la audites — queda fuera de tu alcance.
```

**Qué especificar:** esta regla es la que mantiene al auditor honesto y barato. Sin ella, deriva hacia "yo redactaría este bloque de otra forma" — que es revisión de diseño, trabajo de humanos (o de una sesión de pairing como la que creó los agentes), no de este agente.

---

## Bloque 4 — Formato de salida

```text
1. Veredicto en una línea: al día / deriva detectada (N hallazgos).
2. Tabla de hallazgos: agente y línea → lo que afirma → lo que el repo dice → corrección exacta propuesta.
3. Lista de lo verificado sin deriva (para que se sepa qué se cubrió).
```

**Qué especificar:** la "corrección exacta propuesta" convierte cada hallazgo en un cambio aplicable en segundos por un humano. El punto 3 evita el falso confort: un informe sin hallazgos solo tranquiliza si dice qué se comprobó.

---

## Bloque 5 — Qué NO hacer

```text
- No edites agentes, plantillas ni <ficheros de instrucciones de IA> — propones, un humano aplica.
- No juzgues el diseño de los prompts: solo hechos verificables.
- No audites el código del proyecto en sí — para eso está <el agente revisor>.
```

---

## El patrón completo: auditor + detección en origen

El auditor cubre la deriva *acumulada*, bajo demanda. Su complemento es una regla en el agente revisor de código que caza la deriva *en el momento de introducirse*:

> Deriva de agentes: si el diff cambia algo citado por los prompts de los agentes (targets de build, comandos CLI, rutas, endpoints, valores), señala como hallazgo qué agente queda desactualizado y qué línea corregir. No edites los agentes: solo repórtalo.

Con ambas piezas, la deriva se detecta dos veces: al entrar (revisor, por diff) y al acumularse (auditor, por barrido). Ninguna de las dos edita: el humano siempre aprueba.

## Montaje según la herramienta

| Herramienta | Dónde pegarlo |
|---|---|
| Claude Code | Cuerpo de `.claude/agents/agents-auditor.md`; el bloque 0 va como frontmatter YAML |
| Cursor / Copilot | Regla o instrucciones; requiere que la herramienta pueda ejecutar comandos de verificación |
| CI | Es el agente que mejor degrada a job programado: barrido semanal que abre un issue con el informe |

## Checklist de calidad del prompt terminado

- [ ] La descripción de la ficha incluye "solo reporta, nunca edita".
- [ ] El inventario de ficheros auditados (bloque 1) cubre TODOS los que gobiernan IAs en el repo.
- [ ] Cada categoría del bloque 2 dice dónde se comprueba en este stack.
- [ ] La regla "solo hechos comprobables mecánicamente" está explícita.
- [ ] El formato de salida exige corrección propuesta por hallazgo y lista de lo verificado sin deriva.
- [ ] Existe la regla complementaria de detección en origen en el agente revisor.
