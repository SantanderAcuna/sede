# 01.5 — Normalización de Base de Datos

> Marco teórico y formal de la normalización aplicada al esquema de la Sede Electrónica.
> **Forma normal objetivo:** BCNF, con 4FN donde se detectan MVD no triviales; 5FN evaluada y declarada **no aplicable**.

---

## 1. Edgar F. Codd — Modelo Relacional

Codd [1] introduce el **modelo relacional** en 1970, definiendo:

- **Relación:** subconjunto del producto cartesiano de dominios.
- **Tupla:** elemento de una relación.
- **Atributo:** nombre de un dominio en una relación.
- **Clave candidata:** conjunto mínimo de atributos que identifica unívocamente una tupla.
- **Clave primaria:** clave candidata elegida como identificador principal.
- **Dependencia funcional (FD):** `X → Y` si para cada par de tuplas con el mismo `X`, los valores de `Y` coinciden.

### 1.1 Las tres primeras formas normales (Codd, 1970; Date, 2015)

| Forma normal | Definición | Verificación |
|---|---|---|
| **1FN** | Atributos atómicos; cada celda contiene un valor | No hay grupos repetitivos ni conjuntos multivaluados |
| **2FN** | 1FN + no hay dependencias parciales de la clave | En claves compuestas, cada atributo depende de la clave completa |
| **3FN** | 2FN + no hay dependencias transitivas de no-primos | `X → A` donde `A` es no-primo exige que `X` sea superclave |

---

## 2. Codd — Forma Normal de Boyce-Codd (BCNF, 1974)

Codd [2] introduce la BCNF (Boyce-Codd Normal Form) como refinamiento de la 3FN:

> "Una relación está en BCNF sii todo **determinante** es **superclave**."

Es decir, para toda FD `X → A` que se cumple en la relación, `X` debe ser superclave.

> **Forma normal objetivo de este proyecto:** **BCNF**, salvo en las pocas relaciones donde forzar BCNF rompería la **preservación de dependencias** (no ocurre en este corpus; ver `04-diseno-bd/normalizacion.md`).

---

## 3. Fagin — Cuarta Forma Normal (4FN, 1977)

Fagin [3] introduce 4FN para tratar **dependencias multivaluadas (MVD)**: `X ↠ Y` significa que para cada valor de `X`, los valores de `Y` son independientes de los valores de los demás atributos.

> Una relación está en 4FN sii está en BCNF y para toda MVD no trivial `X ↠ Y`, `X` es superclave.

En este proyecto se detectan **8 MVD no triviales** (ver `04-diseno-bd/normalizacion.md` §5), todas resueltas por extracción a tabla satélite. Resultado: **18 relaciones en 4FN**.

---

## 4. Fagin — Quinta Forma Normal (5FN / PJ/NF, 1979)

Fagin [4] introduce 5FN para tratar **dependencias de reunión (JD)**: una JD `*[R1, R2, ..., Rn]` significa que una tupla está en `R` sii está en cada proyección `Ri`.

> Una relación está en 5FN sii toda JD está implicada por sus claves candidatas.

**Veredicto del proyecto:** **5FN no aplica** (0 JD genuinas; ver `04-diseno-bd/normalizacion.md` §5.3). Toda relación en 4FN está trivialmente en 5FN.

---

## 5. Philip A. Bernstein — Algoritmo de Síntesis (1976)

Bernstein [5] demuestra que, dada una **cobertura mínima** `Fc` de un conjunto de dependencias funcionales `F`, existe una descomposición `ρ` que:

1. Está en **3FN**.
2. **Preserva** todas las dependencias.
3. Es **lossless-join** (al añadir una relación que contenga una clave candidata de R).

> **Procedimiento Bernstein (paso a paso):**
>
> 1. Para cada FD `X → A` de `Fc`, crear una relación con esquema `X ∪ {atributos}` agrupando FD con igual LHS.
> 2. Fusionar relaciones con LHS equivalentes (`X → Y` e `Y → X`).
> 3. Si ninguna relación contiene una clave candidata de `R`, añadir una relación con una clave candidata.
> 4. Eliminar relaciones cuyo esquema esté contenido en otra.
>
> **Aplicación en este proyecto:** ver `04-diseno-bd/normalizacion.md` §1.

---

## 6. C. J. Date — Database Design and Relational Theory

Date [6] es la referencia moderna de la teoría relacional y la normalización. Aporta:

### 6.1 Distinción preservación vs. lossless

- **Preservación de dependencias (FD preservation):** cada FD de `F` debe poder verificarse dentro de **una sola** relación de `ρ`. Si no, se necesitan JOINs para verificarla.
- **Lossless-join (Heath):** `R1 ⋈ R2` reconstruye `R` sin tuplas espurias. Condición suficiente: `(R1 ∩ R2) → R1` o `(R1 ∩ R2) → R2`.

### 6.2 Cuándo NO forzar BCNF

Date explica que **forzar BCNF puede romper la preservación de dependencias**. Ejemplo clásico: `{ciudad, calle} → cp` ∧ `cp → ciudad` (código postal determina ciudad, pero la calle completa determina el CP). Si se descompone para BCNF, la primera FD se pierde.

> En este proyecto: las FD del corpus tienen **LHS disjuntos por entidad** (cada entidad tiene su clave natural propia: `codigo_suit`, `numero_radicado`, `{tipo_doc, num_doc}`, etc.). Por el paso (a) de Bernstein, cada grupo de FD con igual LHS genera **una** relación; como los LHS no se solapan entre entidades, la síntesis reproduce con rigor demostrativo la separación en ~140 relaciones. **No hay conflicto BCNF vs. preservación** en este corpus.

---

## 7. Ramez Elmasri & Shamkant Navathe — Fundamentals of Database Systems

Elmasri y Navathe [7] son la referencia académica estándar. Aportan:

- **Modelado ER** (Chen, Crow's Foot, UML).
- **Reglas de mapeo ER → relacional.**
- **Ejercicios prácticos** de normalización hasta 3FN/BCNF.

### 7.1 Aplicación en este proyecto

- **Modelo ER:** notación **Crow's Foot** en los diagramas Mermaid de `04-diseno-bd/modelo-er.md`.
- **Mapeo ER → relacional:** 13 jerarquías ISA/polimórficas resueltas con class-table, single-table y arco exclusivo (ver `04-diseno-bd/modelo-er.md`).

---

## 8. Abraham Silberschatz, Henry F. Korth & S. Sudarshan — Database System Concepts

Silberschatz et al. [8] complementan con:

- **Árboles B+ e índices.**
- **Particionamiento** (rango, lista, hash).
- **Concurrencia y recuperación.**
- **Optimización de consultas** (planes de ejecución).

### 8.1 Aplicación en este proyecto

- **Índices:** ~46 BTREE + 3 GIN + 4 BRIN + 0 HASH (justificados por álgebra relacional `σ/⋈/π/búsqueda`; ver `04-diseno-bd/indices-y-rendimiento.md`).
- **Particionado:** 6 tablas RANGE (log_auditoria, interop_xroad_transaction, intento_login, notificacion por mes; solicitud y radicado evaluadas y NO particionadas por FK entrantes; ver DELTA R3 físico §D en `_bd/04-modelo-fisico.md`).
- **Concurrencia:** READ COMMITTED por defecto; FOR UPDATE en cupos, pago, consecutivo radicado; SERIALIZABLE en cadena hash log_auditoria (ver `04-diseno-bd/indices-y-rendimiento.md` §3).

---

## 9. Resumen metodológico aplicado

| Decisión | Base teórica | Aplicación |
|---|---|---|
| Síntesis Bernstein como espina dorsal | Bernstein 1976 [5] | Ver `04-diseno-bd/normalizacion.md` §1 |
| Forma normal objetivo = BCNF | Codd 1974 [2] | Ver `04-diseno-bd/normalizacion.md` §2 |
| 4FN donde hay MVD | Fagin 1977 [3] | 8 MVD detectadas y resueltas; ver `04-diseno-bd/normalizacion.md` §5 |
| 5FN evaluada y descartada | Fagin 1979 [4] | 0 JD genuinas; ver `04-diseno-bd/normalizacion.md` §5.3 |
| NO forzar BCNF si rompe FD | Date 2015 [6] | No aplica (LHS disjuntos); ver `04-diseno-bd/normalizacion.md` §0 |
| Crow's Foot en diagramas | Elmasri & Navathe [7] | `04-diseno-bd/modelo-er.md` |
| Índices por álgebra relacional | Silberschatz et al. [8] | `04-diseno-bd/indices-y-rendimiento.md` |

---

## Referencias IEEE (normalización de BD)

[1] E. F. Codd, "A Relational Model of Data for Large Shared Data Banks," *Communications of the ACM*, vol. 13, no. 6, pp. 377–387, Jun. 1970, doi: 10.1145/362384.362685.

[2] E. F. Codd, "Further Normalization of the Data Base Relational Model," *IBM Research Report RJ909*, IBM, 1971; revisado como "Recent Investigations into Relational Data Base Systems," Proc. IFIP Congress, 1974.

[3] R. Fagin, "Multivalued Dependencies and a New Normal Form for Relational Databases," *ACM Transactions on Database Systems*, vol. 2, no. 1, pp. 262–278, Mar. 1977, doi: 10.1145/320544.320571.

[4] R. Fagin, "Normal Forms and Relational Database Operators," *Proceedings of the 1979 ACM SIGMOD International Conference on Management of Data*, pp. 153–160, 1979, doi: 10.1145/582095.582110.

[5] P. A. Bernstein, "Synthesizing Third Normal Form Relations from Functional Dependencies," *ACM Transactions on Database Systems*, vol. 1, no. 4, pp. 277–298, Dec. 1976, doi: 10.1145/320493.320489.

[6] C. J. Date, *Database Design and Relational Theory: Normal Forms and All That Jazz*, 2nd ed. Sebastopol, CA: O'Reilly Media, 2019.

[7] R. Elmasri and S. Navathe, *Fundamentals of Database Systems*, 7th ed. Hoboken, NJ: Pearson, 2016.

[8] A. Silberschatz, H. F. Korth, and S. Sudarshan, *Database System Concepts*, 7th ed. New York, NY: McGraw-Hill Education, 2019.
