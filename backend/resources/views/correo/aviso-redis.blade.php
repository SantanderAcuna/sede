Redis de la Sede Electrónica reporta:

@foreach ($senales as $senal)
- {{ $senal }}
@endforeach

Qué mirar: docs/adr/0009-respaldo-de-la-ultima-respuesta-valida-del-sitio.md (señales de Redis).
Este aviso lo genera `php artisan monitoreo:redis` cada 15 minutos desde el servicio `scheduler`.
