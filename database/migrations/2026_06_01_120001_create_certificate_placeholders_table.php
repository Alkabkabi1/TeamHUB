<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_placeholders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('certificate_template_id')->constrained()->cascadeOnDelete();
            $table->string('binding');
            $table->string('static_text')->nullable();
            // Position and size as fractions (0..1) of the template's natural dimensions.
            $table->decimal('x', 6, 5)->default(0);
            $table->decimal('y', 6, 5)->default(0);
            $table->decimal('width', 6, 5)->default(0.3);
            // Font size as a fraction of the template height (px = font_size * height).
            $table->decimal('font_size', 5, 4)->default(0.04);
            $table->string('font_family')->default('DejaVu Sans');
            $table->string('font_weight')->default('normal');
            $table->string('color')->default('#000000');
            $table->string('align')->default('center');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_placeholders');
    }
};
