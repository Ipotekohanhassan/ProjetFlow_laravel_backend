<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validation des données avec messages personnalisés
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                // Personnalisation des messages d'erreur
                'name.string' => 'Le nom doit être une chaîne de caractères.',
                'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',

                'email.email' => 'Veuillez fournir une adresse email valide.',
                'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
                'email.unique' => 'Cet email est déjà utilisé. Veuillez en choisir un autre.',

                'password.string' => 'Le mot de passe doit être une chaîne de caractères.',
                'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',

                'profile_picture.image' => 'Le fichier téléchargé doit être une image.',
                'profile_picture.mimes' => 'Le fichier doit être de type : jpeg, png, jpg, gif ou svg.',
                'profile_picture.max' => 'La taille de l\'image ne peut pas dépasser 2 Mo.',
            ]);


            // Hachage du mot de passe
            $validatedData['password'] = Hash::make($validatedData['password']);

            // Gestion de la photo de profil
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');

                // Générer un nom unique pour la photo
                $filename = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();

                // Stocker l'image dans le dossier public
                $path = $file->storeAs('profile_pictures', $filename, 'public');

                // Ajouter le chemin du fichier à la donnée de l'utilisateur
                $validatedData['profile_picture'] = $path;
            }

            // Création de l'utilisateur
            $user = User::create($validatedData);

            // Générer le token JWT
            $token = JWTAuth::fromUser($user);

            // Définir la durée de vie du cookie (1 mois)
            $cookieDuration = 60 * 24 * 30; // 1 mois en minutes (60 minutes * 24 heures * 30 jours)

            // Définir le cookie avec le token
            setcookie('jwt_token', $token, time() + $cookieDuration, '/'); // Sécurisé et HTTPOnly

            // Retourner une réponse avec le token
            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'token' => $token,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Gérer les erreurs de validation
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Gérer les autres erreurs
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'inscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function login(Request $request)
    {
        // Validation des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.email' => 'Veuillez fournir une adresse email valide.',
        ]);

        // Vérifier si l'email existe dans la base de données
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email ou mot de passe est incorrect.'], 404);
        }

        // Tenter de générer un token JWT
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Email ou mot de passe est incorrect.'], 401);
        }

        // Définir la durée de vie du cookie (1 mois)
        $cookieDuration = 60 * 24 * 30; // 1 mois en minutes (60 minutes * 24 heures * 30 jours)

        // Définir le cookie avec le token
        setcookie('jwt_token', $token, time() + $cookieDuration, '/', '', true, true);

        // Réponse en cas de succès
        return response()->json(['token' => $token], 200);
    }

    public function getUserInfo(Request $request)
    {
        try {
            // Vérifier la présence du cookie contenant le token
            $token = $request->cookie('jwt_token');

            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies.'], 401);
            }

            // Forcer JWTAuth à utiliser le token
            JWTAuth::setToken($token);

            // Authentifier l'utilisateur
            $user = JWTAuth::authenticate();

            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Retourner les données de l'utilisateur
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
                    'created_at' => $user->created_at->toDateTimeString(),
                ]
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            // Gérer les erreurs liées au token (token expiré, invalide, etc.)
            return response()->json(['error' => 'Erreur d\'authentification.'], 401);
        }
    }


    public function updateProfile(Request $request)
    {
        try {
            // Vérifier la présence du cookie contenant le token
            $token = $request->cookie('jwt_token');

            if (!$token) {
                return response()->json(['error' => 'Token non trouvé dans les cookies.'], 401);
            }

            // Forcer JWTAuth à utiliser le token
            JWTAuth::setToken($token);

            // Authentifier l'utilisateur
            $user = JWTAuth::authenticate();

            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // Validation des données de la requête
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'currentPassword' => 'nullable|string',
                'newPassword' => 'nullable|string|min:6|confirmed',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ], [
                'name.required' => 'Le nom est requis.',
                'email.required' => 'L\'adresse email est requise.',
                'email.email' => 'Veuillez fournir une adresse email valide.',
                'email.unique' => 'Cet email est déjà utilisé.',
                'newPassword.min' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
                'newPassword.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
                'profile_picture.image' => 'Le fichier doit être une image.',
                'profile_picture.mimes' => 'Le fichier doit être de type jpeg, png, jpg, gif ou svg.',
                'profile_picture.max' => 'La taille de l\'image ne peut pas dépasser 2 Mo.',
            ]);

            // Préparer les données à mettre à jour
            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
            ];

            // Vérification du mot de passe actuel
            if ($request->currentPassword && !Hash::check($request->currentPassword, $user->password)) {
                return response()->json(['error' => 'Le mot de passe actuel est incorrect.'], 400);
            }

            // Mise à jour du mot de passe si nécessaire
            if ($request->newPassword) {
                $updateData['password'] = Hash::make($request->newPassword);
            }

            // Mise à jour de la photo de profil si nécessaire
            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture && Storage::exists('public/' . $user->profile_picture)) {
                    Storage::delete('public/' . $user->profile_picture);
                }

                $file = $request->file('profile_picture');
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('profile_pictures', $filename, 'public');
                $updateData['profile_picture'] = $path;
            }

            // Mise à jour des données utilisateur
            DB::table('users')->where('id', $user->id)->update($updateData);

            // Récupérer les données mises à jour
            $updatedUser = DB::table('users')->where('id', $user->id)->first();

            // Retourner les informations mises à jour
            return response()->json([
                'message' => 'Profil mis à jour avec succès.',
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'profile_picture' => $updatedUser->profile_picture ? asset('storage/' . $updatedUser->profile_picture) : null,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllUsers()
    {
        try {
            // Récupérer le token JWT à partir des cookies
            $token = request()->cookie('jwt_token'); // Remplacez 'jwt_token' par le nom de votre cookie si nécessaire

            // Authentifier l'utilisateur à partir du token JWT
            $user = JWTAuth::authenticate($token); // Utilisez JWTAuth pour authentifier l'utilisateur avec le token

            // Vérifiez si l'utilisateur est authentifié
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié.'
                ], 401);
            }

            $currentUserId = $user->id; // ID de l'utilisateur connecté

            // Récupérer tous les utilisateurs sauf l'utilisateur actuel
            $users = User::where('id', '!=', $currentUserId)
                ->get(['id', 'name', 'email', 'profile_picture'])
                ->map(function ($user) use ($currentUserId) {
                    // Vérifie si une demande d'ami est en attente
                    $user->isFriendRequestSent = FriendRequest::where('sender_id', $currentUserId)
                        ->where('receiver_id', $user->id)
                        ->where('status', 'pending')
                        ->exists();
                    return $user;
                });

            return response()->json([
                'success' => true,
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs.',
                'error' => $e->getMessage() // Ajouter l'erreur pour le débogage
            ], 500);
        }
    }
}
