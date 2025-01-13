<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    // Définir les champs pouvant être assignés en masse
    protected $fillable = [
        'user1_id',
        'user2_id',
        'status',
    ];

    // Relation : L'utilisateur 1 dans la relation d'amitié
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    // Relation : L'utilisateur 2 dans la relation d'amitié
    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    // Fonction pour vérifier si deux utilisateurs sont amis
    public static function areFriends($user1Id, $user2Id)
    {
        return self::where(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user1Id)
                ->where('user2_id', $user2Id);
        })->orWhere(function ($query) use ($user1Id, $user2Id) {
            $query->where('user1_id', $user2Id)
                ->where('user2_id', $user1Id);
        })->where('status', 'accepted')->exists();
    }
}
