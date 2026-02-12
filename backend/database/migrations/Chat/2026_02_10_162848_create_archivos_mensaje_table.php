<?php

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
        Schema::create('archivos_mensaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('mensajes')->onDelete('cascade');
            $table->string('tipo'); // 'imagen' | 'documento'
            $table->string('nombre_original');
            $table->string('ruta'); // Path en storage/app/public/chat/{chat_id}/
            $table->unsignedBigInteger('tamaño'); // Tamaño en bytes
            $table->string('mime_type')->nullable(); // image/jpeg, application/pdf, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_mensaje');
    }
};
