<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users'); // Premier utilisateur dans l'amitié
            $table->foreignId('user2_id')->constrained('users'); // Deuxième utilisateur dans l'amitié
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('accepted'); // Statut de la relation
            $table->timestamps();

            // Assurez-vous que les deux utilisateurs sont uniques dans chaque relation d'amitié
            $table->unique(['user1_id', 'user2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
