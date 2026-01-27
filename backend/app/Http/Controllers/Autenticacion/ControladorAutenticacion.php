<?php

namespace App\Http\Controllers\Autenticacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\CambiarPasswordRequest;
use App\Http\Requests\Autenticacion\LoginRequest;
use App\Http\Requests\Autenticacion\ResetPasswordRequest;
use App\Http\Resources\CoachResource;
use App\Http\Resources\ClienteResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ControladorAutenticacion extends Controller
{
    /**
     * Iniciar sesión y obtener token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->activo) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta ha sido desactivada. Contacta al administrador.'],
            ]);
        }

        // Revocar tokens anteriores
        $user->tokens()->delete();

        // Crear nuevo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso.',
            'datos' => [
                'usuario' => $this->formatearUsuario($user),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Cerrar sesión y revocar token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Obtener usuario autenticado con su perfil.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'datos' => $this->formatearUsuario($user),
        ]);
    }

    /**
     * Cambiar contraseña del usuario autenticado.
     */
    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->password_actual, $user->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password_nuevo),
        ]);

        return response()->json([
            'mensaje' => 'Contraseña actualizada correctamente.',
        ]);
    }

    /**
     * Solicitar reset de contraseña.
     */
    public function olvidoPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.exists' => 'No existe una cuenta con ese correo electrónico.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'mensaje' => 'Se ha enviado un enlace de recuperación a tu correo.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['No se pudo enviar el enlace de recuperación.'],
        ]);
    }

    /**
     * Resetear contraseña con token.
     */
    public function resetearPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->update([
                    'password' => Hash::make($password),
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'mensaje' => 'Contraseña restablecida correctamente.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['No se pudo restablecer la contraseña.'],
        ]);
    }

    /**
     * Formatear usuario con su perfil según el rol.
     */
    private function formatearUsuario(User $user): array
    {
        $datos = [
            'id' => $user->id,
            'email' => $user->email,
            'rol' => $user->rol,
            'activo' => $user->activo,
        ];

        // Cargar perfil según rol usando Resources
        if ($user->esCoach()) {
            $user->load('coach');
            $datos['perfil'] = $user->coach ? new CoachResource($user->coach) : null;
        } elseif ($user->esCliente()) {
            $user->load('cliente.coach');
            $datos['perfil'] = $user->cliente ? new ClienteResource($user->cliente) : null;
        }

        return $datos;
    }
}
