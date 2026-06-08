<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthLoginRequest;
use App\Http\Requests\Api\AuthRefreshRequest;
use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;


class AuthController extends Controller
{
    private const ACCESS_TOKEN_TTL_MINUTES = 60;
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    /**
     * @unauthenticated
     */
    public function login(AuthLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $this->resolveUser($data['role'], $data['correo']);

        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales invalidas.',
            ], 401);
        }

        $deviceName = $data['device_name'] ?? $request->userAgent() ?? 'api-client';

        $accessToken = $user->createToken(
            $deviceName.':access',
            ['access'],
            now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES)
        );

        $refreshToken = $user->createToken(
            $deviceName.':refresh',
            ['refresh'],
            now()->addDays(self::REFRESH_TOKEN_TTL_DAYS)
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $accessToken->plainTextToken,
            'access_token_expires_at' => $accessToken->accessToken->expires_at?->toIso8601String(),
            'refresh_token' => $refreshToken->plainTextToken,
            'refresh_token_expires_at' => $refreshToken->accessToken->expires_at?->toIso8601String(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function refresh(AuthRefreshRequest $request): JsonResponse
    {
        $tokenValue = $request->validated()['refresh_token'];
        $token = PersonalAccessToken::findToken($tokenValue);

        if (!$token || !$token->can('refresh') || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json([
                'message' => 'Refresh token invalido o expirado.',
            ], 401);
        }

        $tokenable = $token->tokenable;

        if (!$tokenable) {
            return response()->json([
                'message' => 'Refresh token invalido.',
            ], 401);
        }

        $token->delete();

        $deviceName = explode(':', $token->name)[0] ?: 'api-client';

        $accessToken = $tokenable->createToken(
            $deviceName.':access',
            ['access'],
            now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES)
        );

        $refreshToken = $tokenable->createToken(
            $deviceName.':refresh',
            ['refresh'],
            now()->addDays(self::REFRESH_TOKEN_TTL_DAYS)
        );

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $accessToken->plainTextToken,
            'access_token_expires_at' => $accessToken->accessToken->expires_at?->toIso8601String(),
            'refresh_token' => $refreshToken->plainTextToken,
            'refresh_token_expires_at' => $refreshToken->accessToken->expires_at?->toIso8601String(),
            'user' => $this->userPayload($tokenable),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()?->delete();

        return response()->json([
            'message' => 'Todas las sesiones fueron cerradas.',
        ]);
    }

    private function resolveUser(string $role, string $correo): Docente|Estudiante|null
    {
        return $role === 'docente'
            ? Docente::query()->where('correo', $correo)->first()
            : Estudiante::query()->where('correo', $correo)->first();
    }

    private function userPayload(Model $user): array
    {
        if ($user instanceof Docente) {
            $user->loadMissing('carreras');

            return [
                'type' => 'docente',
                'id' => $user->id_docente,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'correo' => $user->correo,
                'id_universidad' => $user->id_universidad,
                'es_jefe_carrera' => (int) $user->es_jefe_carrera,
                'descripcion' => $user->descripcion,
                'carrera_ids' => $user->carreras->pluck('id_carrera')->values(),
            ];
        }

        return [
            'type' => 'estudiante',
            'id' => $user->id_estudiante,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'correo' => $user->correo,
            'id_universidad' => $user->id_universidad,
            'id_modulo' => $user->id_modulo,
        ];
    }
}
