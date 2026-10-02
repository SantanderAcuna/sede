<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('etiqueta', 120);
            $table->string('ruta', 500)->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->enum('tipo', ['interno', 'externo', 'ancla', 'modal'])->default('interno');
            $table->json('roles')->nullable()->comment('Roles que ven este ítem: sitio, panel, admin. Null=todos.');
            $table->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->timestamps();

            $table->index(['menu_id', 'orden']);
            $table->index('visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
