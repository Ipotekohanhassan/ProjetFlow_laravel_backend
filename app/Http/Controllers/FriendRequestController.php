<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class FriendRequestController extends Controller
{
    /**
     * Envoie une demande d'ami.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendFriendRequest(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'receiver_id' => 'required|exists:users,id|different:sender_id', // Vérifie que l'utilisateur existe et n'est pas l'utilisateur actuel
            ]);

            // Récupérer le token depuis le cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Vérifie si une demande existe déjà
            $existingRequest = FriendRequest::where('sender_id', $userId)
                ->where('receiver_id', $validated['receiver_id'])
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return response()->json(['message' => 'Demande déjà envoyée à cet utilisateur.'], 400);
            }

            // Crée la demande d'ami
            FriendRequest::create([
                'sender_id' => $userId,
                'receiver_id' => $validated['receiver_id'],
                'status' => 'pending',
            ]);

            return response()->json(['message' => 'Demande d\'ami envoyée.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Accepte une demande d'ami.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function acceptFriendRequest(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'id' => 'required|exists:friend_requests,id',
            ]);

            // Récupérer le token depuis le cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Récupérer la demande d'ami
            $friendRequest = FriendRequest::find($validated['id']);

            // Vérifier si la demande existe
            if (!$friendRequest) {
                return response()->json(['error' => 'Demande d\'ami introuvable.'], 404);
            }

            // Vérifier si l'utilisateur est celui qui a reçu la demande
            if ($friendRequest->receiver_id !== $userId) {
                return response()->json(['message' => 'Vous ne pouvez pas accepter cette demande.'], 403);
            }

            // Mettre à jour le statut de la demande pour "accepted"
            $friendRequest->update(['status' => 'accepted']);

            // Créer une relation d'amitié dans la table friendships
            Friendship::create([
                'user1_id' => $friendRequest->sender_id,
                'user2_id' => $friendRequest->receiver_id,
                'status' => 'accepted',
            ]);

            return response()->json(['message' => 'Demande d\'ami acceptée.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Rejette une demande d'ami.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function rejectFriendRequest(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'request_id' => 'required|exists:friend_requests,id',
            ]);

            // Récupérer le token du cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Récupérer la demande d'ami
            $friendRequest = FriendRequest::find($validated['request_id']);

            // Vérifier si la demande existe
            if (!$friendRequest) {
                return response()->json(['error' => 'Demande d\'ami introuvable.'], 404);
            }

            // Vérifier si l'utilisateur est celui qui a reçu la demande
            if ($friendRequest->receiver_id !== $userId) {
                return response()->json(['message' => 'Vous ne pouvez pas rejeter cette demande.'], 403);
            }

            // Mettre à jour le statut de la demande pour "rejected"
            $friendRequest->update(['status' => 'rejected']);

            return response()->json(['message' => 'Demande d\'ami rejetée.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Annule une demande d'ami envoyée par l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancelFriendRequest(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'request_id' => 'required|exists:friend_requests,id',
            ]);

            // Récupérer le token du cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Récupérer la demande d'ami
            $friendRequest = FriendRequest::find($validated['request_id']);

            // Vérifier si la demande existe et si l'utilisateur est celui qui a envoyé la demande
            if (!$friendRequest) {
                return response()->json(['error' => 'Demande d\'ami introuvable.'], 404);
            }

            if ($friendRequest->sender_id !== $userId) {
                return response()->json(['message' => 'Vous ne pouvez pas annuler cette demande.'], 403);
            }

            // Supprimer la demande d'ami (annulation)
            $friendRequest->delete();

            return response()->json(['message' => 'Demande d\'ami annulée.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Liste des demandes d'amis en attente.
     *
     * @return \Illuminate\Http\Response
     */
    public function listPendingFriendRequests(Request $request)
    {
        try {
            // Récupérer le token du cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Liste des demandes d'amis en attente avec les informations de l'expéditeur
            $pendingRequests = FriendRequest::where('receiver_id', $userId)
                ->where('status', 'pending')
                ->with('sender:id,name,email,profile_picture') // Inclure les données de l'expéditeur (ajustez les colonnes selon vos besoins)
                ->get();

            return response()->json(['pending_requests' => $pendingRequests]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Liste des relations d'amitié de l'utilisateur.
     *
     * @return \Illuminate\Http\Response
     */
    public function listFriends(Request $request)
    {
        try {
            // Récupérer le token du cookie
            $token = $request->cookie('jwt_token');

            // Vérifier si le token existe
            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies'], 401);
            }

            // Authentifier l'utilisateur à partir du token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            // Si l'utilisateur n'est pas authentifié
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Récupérer l'ID de l'utilisateur authentifié
            $userId = $user->id;

            // Liste des amis de l'utilisateur
            $friends = Friendship::where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                    ->orWhere('user2_id', $userId);
            })
                ->where('status', 'accepted')
                ->get();

            return response()->json(['friends' => $friends]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }
}
