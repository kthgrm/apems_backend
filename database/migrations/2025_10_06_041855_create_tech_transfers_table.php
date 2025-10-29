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
        Schema::create('tech_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(College::class)->constrained()->restrictOnDelete();

            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->string('purpose');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('tags');
            $table->string('leader');
            $table->string('deliverables')->nullable();

            $table->string('agency_partner');
            $table->string('contact_person');
            $table->string('contact_phone');

            $table->enum('copyright', ['yes', 'no', 'pending'])->default('no');
            $table->text('ip_details')->nullable();

            $table->json('attachment_paths')->nullable();
            $table->string('attachment_link')->nullable();

            $table->boolean('is_archived')->default(false);

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tech_transfers');
    }
};
