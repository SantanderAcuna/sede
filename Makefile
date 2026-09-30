# Puertas y tareas de la Sede Electrónica.
#
# Cada puerta es un objetivo. Ninguna se salta y todas corren en la canalización.
# La regla que gobierna este archivo: si algo se puede comprobar, se comprueba
# aquí y no en la cabeza de quien despliega.

SHELL := /bin/bash
.DEFAULT_GOAL := ayuda

BACKEND  := backend
PANEL    := panel
SITIO    := sitio
CONTRATO := contract/openapi.yaml

# Para que las herramientas de Node no escriban fuera del proyecto.
export npm_config_cache := $(CURDIR)/.cache/npm
export XDG_CACHE_HOME   := $(CURDIR)/.cache

.PHONY: ayuda
ayuda: ## Muestra los objetivos disponibles
	@echo "Puertas y tareas de la Sede Electrónica"
	@echo
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

# ---------------------------------------------------------------------------
# Instalación
# ---------------------------------------------------------------------------

.PHONY: instalar
instalar: ## Instala las dependencias de las tres aplicaciones
	cd $(BACKEND) && composer install --no-interaction
	cd $(PANEL) && npm ci
	cd $(SITIO) && npm ci
	$(MAKE) tipos

.PHONY: tipos
tipos: ## Genera los tipos de TypeScript desde el contrato
	cd $(PANEL) && npx openapi-typescript ../$(CONTRATO) -o src/types/openapi.d.ts --read-write-markers
	cd $(SITIO) && npx openapi-typescript ../$(CONTRATO) -o types/api.d.ts --read-write-markers

.PHONY: preparar
preparar: ## Prepara la base de datos local con datos de ejemplo
	cd $(BACKEND) && php artisan migrate:fresh --seed

# ---------------------------------------------------------------------------
# La puerta completa
# ---------------------------------------------------------------------------

.PHONY: comprobar
comprobar: contrato formato-verificar analisis pruebas unidad tipos compilar diseno accesibilidad ## Ejecuta TODAS las puertas
	@echo
	@echo "Todas las puertas en verde."

# ---------------------------------------------------------------------------
# Contrato
# ---------------------------------------------------------------------------

.PHONY: contrato
contrato: ## Valida el contrato y comprueba que no derive de las rutas
	cd $(PANEL) && npx --yes @redocly/cli@2.55.0 lint ../$(CONTRATO)
	cd $(BACKEND) && php artisan test --filter=ContratoDeriva

.PHONY: simular
simular: ## Levanta el simulador del contrato en el puerto 4010
	docker run --rm -p 4010:4010 \
		-v $(CURDIR)/$(CONTRATO):/tmp/openapi.yaml \
		stoplight/prism:4 mock -h 0.0.0.0 /tmp/openapi.yaml

# ---------------------------------------------------------------------------
# Backend
# ---------------------------------------------------------------------------

.PHONY: pruebas
pruebas: ## Ejecuta la suite del backend
	cd $(BACKEND) && php artisan test

.PHONY: analisis
analisis: ## Analiza el backend en nivel 8
	cd $(BACKEND) && ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress

.PHONY: formato
formato: ## Aplica el formato al backend
	cd $(BACKEND) && ./vendor/bin/pint

.PHONY: formato-verificar
formato-verificar: ## Verifica el formato sin modificar archivos
	cd $(BACKEND) && ./vendor/bin/pint --test

# ---------------------------------------------------------------------------
# Frontends
# ---------------------------------------------------------------------------

.PHONY: unidad
unidad: ## Ejecuta las pruebas unitarias de los dos frontends
	cd $(PANEL) && npm run test -- --run
	cd $(SITIO) && npm run test -- --run

.PHONY: compilar
compilar: ## Verifica tipos y compila los dos frontends
	cd $(PANEL) && npm run build
	cd $(SITIO) && npm run build

.PHONY: diseno
diseno: ## Comprueba los criterios de diseño sobre el navegador
	cd $(PANEL) && node tests/conformidad-diseno.mjs

.PHONY: accesibilidad
accesibilidad: ## Audita la accesibilidad con axe sobre todas las vistas
	cd $(PANEL) && node tests/accesibilidad.mjs

# ---------------------------------------------------------------------------
# Infraestructura
# ---------------------------------------------------------------------------

.PHONY: pila
pila: ## Comprueba que la pila se resuelve sin levantar nada
	@docker compose config --quiet && echo "La pila se resuelve sin errores."

.PHONY: arriba
arriba: ## Levanta la pila de desarrollo
	docker compose up -d

.PHONY: abajo
abajo: ## Detiene la pila de desarrollo
	docker compose down

.PHONY: registros
registros: ## Sigue los registros de la pila
	docker compose logs -f --tail=50

.PHONY: imagenes
imagenes: ## Comprueba que las bases están fijadas por resumen
	@bash scripts/verificar-imagenes.sh

.PHONY: respaldo
respaldo: ## Crea una copia de seguridad y comprueba que se puede restaurar
	@bash scripts/verificar-respaldo.sh

# ---------------------------------------------------------------------------
# Utilidades
# ---------------------------------------------------------------------------

.PHONY: limpiar
limpiar: ## Limpia cachés y artefactos generados
	cd $(BACKEND) && php artisan optimize:clear
	rm -rf $(PANEL)/dist $(SITIO)/.output $(SITIO)/.nuxt

.PHONY: limpiar-todo
limpiar-todo: limpiar ## Limpia además las dependencias instaladas
	rm -rf $(BACKEND)/vendor $(PANEL)/node_modules $(SITIO)/node_modules
