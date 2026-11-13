<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Datos de prueba
|--------------------------------------------------------------------------
|
| `staging` y producción se despliegan los dos con `APP_ENV=production`
| (`deploy/plantilla.env`), así que el nombre del entorno no sirve para decidir
| dónde pueden existir datos inventados. Esta bandera sí: es **falsa por
| defecto**, no está en ningún archivo de `deploy/` (así no puede viajar a
| producción con un despliegue) y se enciende sólo en el comando que siembra a
| mano, con `-e PERMITIR_DATOS_DE_PRUEBA=1`. Los sembradores de prueba se niegan a
| correr fuera de `local`/`testing` si no está encendida.
|
*/

return [

    'permitir' => (bool) env('PERMITIR_DATOS_DE_PRUEBA', false),

];
