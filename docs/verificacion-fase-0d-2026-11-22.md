# Verificación de la Fase 0D, integridad de la base de datos (2026-11-22)

Estado: **cerrada por José el 2026-11-22.** Este documento solo registra lo medido; las decisiones están en
`docs/adr/0007-guarda-del-esquema-con-trinquete.md` y `docs/adr/0008-mediateca-uso-y-archivo-sin-dueno-polimorfico.md`.

## Tareas y evidencia en el repositorio (HECHO: cada archivo existe en `develop`)

| Tarea | Migración | Prueba |
|---|---|---|
| D1 Guarda del esquema | (CI) | `GuardaEsquemaTest`, `InspectorEsquemaTest`, `esquema.linea-base.json` |
| D2 Fechas con zona | `2026_11_19_300000_fechas_con_zona_horaria` | `FechasConZonaMigracionTest` |
| D3 Índices de claves foráneas | `2026_11_19_200000_indices_de_claves_foraneas` | `IndicesDeClavesForaneasMigracionTest` |
| D4 CHECK de enumeraciones | `2026_11_19_400000_checks_de_enumeraciones` | `ChecksDeEnumeracionesMigracionTest`, `ChecksDeEnumeracionesTest` |
| D5 Requisito del mismo trámite | `2026_11_19_500000_requisito_con_paso_de_su_tramite` | `RequisitosDelMismoTramiteMigracionTest`, `RequisitosDelMismoTramiteTest` |
| D6 Bitácora de solo inserción | `2026_11_19_600000_bitacora_de_solo_insercion` | `BitacoraDeSoloInsercionTest` |
| D7 Límites por rol | `2026_11_19_000000_crear_roles_de_base_de_datos` y `docs/limites-por-rol-bd.md` | pruebas de roles de la 0A |
| D8 Mediateca, expandir | `2026_11_19_700000_mediateca_expandir` | `MediatecaExpandirMigracionTest` |
| D9 Mediateca, lecturas y escrituras | `2026_11_20_100000_mediateca_resincronizar` | `MediatecaResincronizarMigracionTest`, `LimpiezaSinUsoTest` |
| D10a/b/c/d Mediateca, uso, contraer, verificación, aviso | `2026_11_20_200000_mediateca_uso`, `2026_11_20_300000_mediateca_contraer` | `MediatecaUsoMigracionTest`, `MediatecaContraerMigracionTest`, `VerificarArchivosTest`, `AgendaDeHuellasTest`, `LimpiezaSinUsoTest` |
| D11 Cierre | `docs/adr/0008-…` | este documento |

## Verificado en staging para D8–D11 (2026-11-19 a 2026-11-22)

| Comprobación | Resultado |
|---|---|
| Consulta de solo lectura de José tras D10b | `file_media` con 12 columnas, sin `collection`, `model_type`, `model_id` ni `metadata`; `hash_sha256` NOT NULL; 0 disparadores; `uso`: contenido 9, documento 14, imagen 31 |
| `archivos:verificar` a mano (José) | «54 archivo(s) verificado(s) sin diferencias.» |
| Recorrido con clics reales tras D10a y tras D10b | noticia con imagen principal y de texto (publicar, reemplazar principal, borrar), banner con imagen, política con documento (adjuntar, quitar, eliminar); API sin `model_type`/`model_id`/`metadata` y con `collection`; todo lo creado se borró |
| `archivos:limpiar-sin-uso --simular` tras D10d (José) | «13 documento(s) sin uso» con sus uuid; el documento en uso no se lista |
| CI y despliegue de #679 y #680 | 8 checks, despliegue a pruebas y 4 navegadores en verde |

## Pendientes que no bloquean el cierre

- Los 13 documentos de prueba sin uso en staging se quedan: no hay camino soportado para borrarlos (la API responde 409 por la Ley 594/2000) y José decidió no borrarlos. La limpieza nocturna los lista.
- Retención documental de los documentos oficiales: sigue pendiente la respuesta de jurídica (Ley 594/2000).
- Sin medir: la duración de `archivos:verificar` con el volumen de producción.
- Sin revisar: 2 vulnerabilidades altas de Dependabot.
- INFERENCIA no verificable desde el repositorio: que ningún consumidor externo de la API lea `model_type`, `model_id` ni `metadata` (José respondió que no hay).
- Los archivos 0004, 0005 y 0006 siguen en PROPUESTA: dependen del cierre de las entregas 0A y 0B, que este documento no decide.
