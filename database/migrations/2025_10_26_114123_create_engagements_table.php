<?php

use App\Models\College;
use App\Models\User;
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
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(College::class)->constrained()->restrictOnDelete();

            $table->string('agency_partner');
            $table->string('location');
            $table->string('activity_conducted');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('number_of_participants');
            $table->string('faculty_involved');
            $table->string('narrative');

            $table->json('attachment_paths')->nullable();
            $table->string('attachment_link')->nullable();

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
