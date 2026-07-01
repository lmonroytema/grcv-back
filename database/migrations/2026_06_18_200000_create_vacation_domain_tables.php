<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 50)->unique();
            });
        }

        if (!Schema::hasTable('colaboradores')) {
            Schema::create('colaboradores', function (Blueprint $table): void {
                $table->id();
                $table->string('apellidos_y_nombres');
                $table->string('n_documento', 20)->unique();
                $table->date('fecha_ingreso');
                $table->string('area')->nullable();
                $table->string('correo')->nullable();
                $table->string('aprobador_1')->nullable();
                $table->string('aprobador_2')->nullable();
            });
        }

        if (!Schema::hasTable('vacation_requests')) {
            Schema::create('vacation_requests', function (Blueprint $table): void {
                $table->id();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->string('email', 50);
                $table->string('last_name', 50);
                $table->string('mother_name', 50);
                $table->string('first_name', 50);
                $table->string('second_name', 50)->nullable();
                $table->string('dni', 20);
                $table->string('area', 50);
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('days');
                $table->string('confirmation_image', 100);
                $table->string('pdf_file', 100)->nullable();
                $table->integer('estado')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_requests');
        Schema::dropIfExists('colaboradores');
        Schema::dropIfExists('roles');
    }
};
