<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Vérifier la présence du cookie
            $token = $request->cookie('jwt_token');
            Log::info('Middleware Auth: Vérification du cookie jwt_token', ['token' => $token]);

            if (!$token) {
                Log::warning('Middleware Auth: Token non trouvé dans les cookies');
                return response()->json(['message' => 'Token non trouvé'], 401);
            }

            // Forcer JWTAuth à utiliser le token
            JWTAuth::setToken($token);
            Log::info('Middleware Auth: Token JWT défini');

            // Authentifier l'utilisateur
            $user = JWTAuth::authenticate();
            Log::info('Middleware Auth: Utilisateur authentifié', ['user_id' => $user->id]);

            // Si l'utilisateur est authentifié, continuer la requête
            return $next($request);
        } catch (JWTException $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                Log::error('Middleware Auth: Token invalide');
                return response()->json(['message' => 'Token invalide'], 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                Log::error('Middleware Auth: Token expiré');
                return response()->json(['message' => 'Token expiré'], 401);
            } else {
                Log::error('Middleware Auth: Erreur de token', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Erreur de token'], 401);
            }
        }
    }
}
