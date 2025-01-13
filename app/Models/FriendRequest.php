<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    use HasFactory;

    // Définir les champs pouvant être assignés en masse
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
    ];

    // Relation : Un utilisateur a envoyé cette demande
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relation : Un utilisateur a reçu cette demande
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
