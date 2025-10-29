<?php

use App\Models\TechTransfer;
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
        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(TechTransfer::class)->constrained()->restrictOnDelete();

            $table->string('modality');
            $table->string('tv_channel')->nullable();
            $table->string('radio')->nullable();
            $table->string('online_link')->nullable();
            $table->string('time_air')->nullable();
            $table->string('period')->nullable();
            $table->string('partner_agency')->nullable();
            $table->string('hosted_by')->nullable();

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
        Schema::dropIfExists('modalities');
    }
};
