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
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    private const ACCESS_TOKEN_TTL_MINUTES = 60;
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login con correo y password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['correo', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'correo', type: 'string', format: 'email', example: 'demo@correo.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'secret123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['docente', 'estudiante'], example: 'docente'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'postman'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login exitoso'),
            new OA\Response(response: 401, description: 'Credenciales invalidas'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Renovar access token con refresh token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token renovado'),
            new OA\Response(response: 401, description: 'Refresh token invalido'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Datos del usuario autenticado',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Usuario autenticado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesion del token actual',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Sesion cerrada'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout-all',
        summary: 'Cerrar todas las sesiones del usuario',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Sesiones cerradas'),
        ]
    )]
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
