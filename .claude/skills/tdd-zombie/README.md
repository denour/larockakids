# /tdd-zombie - Skill de TDD con metodologia ZOMBIE

## Que es?

Un skill de Claude Code que te guia paso a paso para desarrollar funcionalidades usando **Test-Driven Development (TDD)** con la metodologia **ZOMBIE** de James Grenning.

## Como usarlo

```bash
/tdd-zombie [descripcion de lo que quieres desarrollar]
```

### Ejemplos

```bash
/tdd-zombie crear servicio de reportes de asistencia mensual
/tdd-zombie agregar metodo para filtrar ninos por alergias
/tdd-zombie validar que un QR no se pueda asignar dos veces
```

## Que es ZOMBIE?

ZOMBIE es un acronimo que define el orden en que debes escribir tus tests:

| Fase | Significado | Que se prueba |
|------|-------------|---------------|
| **Z** | Zero (Cero) | Estado inicial: listas vacias, contadores en cero, valores por defecto |
| **O** | One (Uno) | Un solo caso: crear un registro, procesar un item |
| **M** | Many (Muchos) | Multiples casos: varios registros, orden, filtros |
| **B** | Boundaries (Limites) | Valores extremos: null vs vacio, maximos, fechas limite |
| **I** | Interface (Interfaz) | API publica: parametros, tipos de retorno, consistencia |
| **E** | Exceptional (Excepciones) | Errores: modelo no encontrado, validacion fallida, permisos |

## Ciclo Red-Green-Refactor

En cada fase de ZOMBIE, el skill sigue el ciclo clasico de TDD:

```
RED     -> Escribe un test que falle
GREEN   -> Escribe el codigo minimo para que pase
REFACTOR -> Mejora el codigo sin cambiar comportamiento
```

## Que vas a ver

El skill te muestra el progreso asi:

```
## ZOMBIE - Fase Z: Cero
Estado: RED
Test: test_returns_empty_collection_when_no_records
```

Y al completar cada fase:

```
Fase Z completada. Tests pasando: 1/1
Siguiente fase: O - Uno
```

## Reglas del skill

- Solo escribe **un test a la vez** antes de ejecutarlo
- Solo escribe codigo de produccion **cuando un test lo requiere**
- Usa las **factories existentes** del proyecto
- Sigue las **convenciones de tests** del proyecto
- Al finalizar ejecuta `vendor/bin/pint --dirty` para formatear
- Al terminar pregunta si quieres correr el suite completo

## Ubicacion

```
.claude/skills/tdd-zombie/
├── SKILL.md   # Instrucciones del skill (en ingles para Claude)
└── README.md  # Esta documentacion (en espanol para ti)
```
