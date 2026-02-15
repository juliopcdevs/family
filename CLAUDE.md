# CLAUDE.md

## Modo de Trabajo: Spec-First Development

Este proyecto sigue un flujo en dos fases. NO escribas código hasta que se complete la Fase 1.

---

## FASE 1: Descubrimiento y Especificación

Cuando el usuario describa una idea de proyecto, activa el modo entrevista:

### Paso 1 — Entender la visión
Haz preguntas breves y concretas para cubrir estos bloques. NO hagas todas a la vez.
Ve bloque a bloque, máximo 2-3 preguntas por mensaje:

1. **Qué y para qué**: ¿Qué hace la app? ¿Qué problema resuelve?
2. **Stack preferido**: ¿Tienes preferencias tecnológicas o elijo yo?
3. **Datos**: ¿Qué entidades/objetos maneja? (usuarios, productos, pedidos...)
4. **Funcionalidades core**: ¿Qué puede HACER el usuario? (crear, listar, filtrar...)
5. **Pantallas**: ¿Qué páginas/vistas necesitas?
6. **Auth**: ¿Necesita login o es abierta?
7. **Scope**: ¿Qué NO debe tener esta POC?

### Paso 2 — Generar la Spec
Cuando tengas suficiente contexto, genera estos archivos en el repo:

```
docs/
├── SPEC.md           # Especificación completa
tasks/
├── 01-setup.md       # Inicialización del proyecto
├── 02-data-layer.md  # Modelos y acceso a datos
├── 03-api.md         # Endpoints / server actions
├── 04-ui.md          # Pantallas y componentes
├── 05-integration.md # Conectar todo + seed data
└── 06-polish.md      # Estilos, UX, edge cases
```

### Paso 3 — Revisión
Después de generar los archivos:
- Muestra un RESUMEN ejecutivo al usuario (no el doc entero)
- Pregunta: "¿Quieres revisar o cambiar algo antes de empezar?"
- Itera según feedback hasta que el usuario diga que está listo
- El usuario puede decir cosas como "la tabla X necesita campo Y",
  "quita la pantalla Z", "usa Prisma en vez de Drizzle", etc.
- Actualiza los archivos en el repo con cada cambio

### Señal de arranque
La Fase 1 termina SOLO cuando el usuario diga explícitamente algo como:
"dale", "arranca", "empieza", "adelante", "go", "desarrolla", "build it"

---

## FASE 2: Desarrollo

Cuando el usuario dé luz verde:

1. Lee docs/SPEC.md completo
2. Ejecuta las tareas en tasks/ en orden numérico
3. Después de cada tarea:
   - Verifica que funciona (ejecuta el proyecto, corre tests si hay)
   - Haz commit: `feat(scope): descripción`
   - Pasa a la siguiente
4. Si algo falla, intenta arreglarlo (máx 3 intentos)
5. Si no puedes, documenta en ERRORS.md y sigue
6. Al terminar todas las tareas, ejecuta el proyecto y confirma que funciona

### Reglas durante el desarrollo
- NO inventes features fuera de la spec
- NO preguntes al usuario (la spec tiene todo)
- Si algo es ambiguo, elige lo más simple
- Archivos máx 150 líneas, dividir si crecen
- TypeScript strict (si aplica)
- Imports absolutos con @/

---

## Convenciones Generales
- Commits: conventional commits (feat, fix, chore, docs)
- Nombres de archivo: kebab-case
- Componentes: PascalCase
- Variables/funciones: camelCase
- Preferir composición sobre herencia
- Preferir funciones pequeñas y puras
- Manejar errores explícitamente, no silenciar
