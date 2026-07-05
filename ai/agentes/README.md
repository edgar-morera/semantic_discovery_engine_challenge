# Pautas para crear agentes de IA en un proyecto

Guías para diseñar agentes especializados (revisión de código, pruebas E2E, documentación) en cualquier proyecto y con cualquier herramienta. Los ejemplos concretos usan Claude Code (los agentes de este repo están en `.claude/agents/`), pero los principios son independientes de la herramienta.

## Guías por tipo de agente

- [Agente de revisión de código](agente-revision-codigo.md)
- [Agente de pruebas funcionales E2E](agente-pruebas-e2e.md)
- [Agente de documentación](agente-documentacion.md)
- [Agente auditor de agentes](agente-auditor.md) — meta-nivel: verifica que los prompts de los demás agentes sigan siendo verdad

## Principios comunes a todos los agentes

Estos aplican a cualquier tipo de agente; cada guía asume que los conoces.

### 1. El agente arranca en frío

Un agente no ve tu conversación ni tu historial: solo recibe su prompt de sistema, las instrucciones del proyecto (CLAUDE.md o equivalente) y la tarea concreta. Consecuencia práctica: **todo el contexto operativo debe estar escrito en su prompt** — comandos exactos, rutas clave, particularidades del proyecto (asincronía, cachés, datos compartidos). Si dependes de que "lo descubra", gastará tiempo y tokens redescubriéndolo en cada invocación, o directamente se equivocará.

### 2. Una responsabilidad por agente

Tres agentes pequeños con prompts concretos funcionan mejor que un mega-agente "QA" genérico. Un prompt enfocado produce resultados enfocados; uno que intenta cubrir todo produce revisiones superficiales de todo.

### 3. La descripción es el mecanismo de delegación

En herramientas con delegación automática (Claude Code, por ejemplo), el orquestador decide a qué agente enviar una tarea leyendo su campo `description`. Escríbela como condición de disparo — "Usar cuando se pida X" — no como descripción de marketing. Una descripción vaga significa que el agente no se usa nunca, o se usa para lo que no toca.

### 4. Mínimo privilegio en herramientas

Restringe las herramientas del agente a su rol. Es el principal control de seguridad y de foco:

| Rol | Necesita | No debe tener |
|---|---|---|
| Revisor | lectura, ejecución de análisis | escritura (no debe "arreglar" cuando debe opinar) |
| Tester E2E | ejecución (curl, docker), lectura | escritura de código |
| Documentador | lectura y escritura | — |

### 5. Modelo según la tarea, no según el prestigio

Revisión de código y análisis arquitectónico se benefician de un modelo potente. Ejecutar peticiones HTTP y comparar respuestas, o redactar guiado por un checklist, funciona bien con un modelo intermedio. Fija el modelo explícitamente en cada agente para que el coste sea predecible y no dependa de la sesión que lo invoque.

### 6. No dupliques lo que ya existe

Antes de crear un agente, inventaría lo que el proyecto y la herramienta ya ofrecen: comandos de review integrados, generación de OpenAPI, linters, pipelines de CI. Un agente aporta valor cuando codifica **criterios propios del proyecto** que las herramientas genéricas no conocen; si solo repite lo que hace un linter, es coste sin beneficio.

### 7. Los agentes protegen decisiones, no las cuestionan

Las decisiones arquitectónicas ya tomadas se escriben en el prompt como reglas a hacer cumplir ("decisión cerrada: señala cualquier cambio que la revierta"), no como afirmaciones que el agente pueda interpretar como opinables. Distingue siempre en el prompt entre *convención a verificar* y *decisión a proteger*.

### 8. Los agentes se desactualizan

Un prompt con comandos y rutas concretas es más eficaz pero caduca cuando el proyecto cambia. Trata los agentes como código: viven en el repositorio, se versionan, se revisan en los PR que cambian aquello que describen. Señal de alarma: un agente que falla "misteriosamente" suele estar ejecutando comandos que ya no existen. Para sistematizar esta vigilancia hay un patrón dedicado — ver [Agente auditor de agentes](agente-auditor.md): detección en origen (regla en el revisor) + barrido bajo demanda (auditor), sin que ningún agente edite a otro.

## Equivalencias entre herramientas

El concepto es portable aunque la sintaxis cambie:

| Herramienta | Mecanismo |
|---|---|
| Claude Code | Subagentes en `.claude/agents/*.md` (frontmatter: `name`, `description`, `tools`, `model` + cuerpo como prompt de sistema) |
| GitHub Copilot | Code review integrado en PRs + instrucciones en `.github/copilot-instructions.md` |
| Cursor | Reglas en `.cursor/rules/` (aplicables por patrón de archivos) |
| OpenAI Codex / otros | Convención `AGENTS.md` en la raíz del repo |
| CI genérico | Bot que invoca un LLM por API con el prompt del agente + el diff como entrada |

Lo que se transfiere entre herramientas es el **contenido del prompt** (contexto del proyecto, reglas, checklist, formato de salida) — eso es el 90 % del valor. La envoltura (frontmatter, ubicación del archivo) es el 10 % restante y se adapta en minutos.
