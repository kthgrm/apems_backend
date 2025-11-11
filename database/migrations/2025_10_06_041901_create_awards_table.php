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
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(College::class)->constrained()->restrictOnDelete();

            // Basic Information
            $table->string('award_name');
            $table->string('description');
            $table->date('date_received');

            // Event Details
            $table->string('event_details');
            $table->string('location');
            $table->string('awarding_body');
            $table->string('people_involved');

            $table->json('attachment_paths')->nullable();
            $table->string('attachment_link')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('remarks')->nullable();

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
