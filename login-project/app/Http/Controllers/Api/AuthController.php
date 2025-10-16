<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário
     */
    public function register(Request $request)
    {
        try {
            Log::info('Tentativa de registro de novo usuário', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            Log::debug('Dados validados para registro', [
                'name' => $validated['name'],
                'email' => $validated['email']
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Log::info('Usuário registrado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ]);

            return response()->json([
                'message' => 'Usuário registrado com sucesso',
                'user' => $user
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Falha na validação do registro', [
                'email' => $request->email,
                'errors' => $e->errors(),
                'ip' => $request->ip()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('Erro ao registrar usuário', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login do usuário
     */
    public function login(Request $request)
    {
        try {
            Log::info('Tentativa de login', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            Log::debug('Dados de login validados', [
                'email' => $request->email
            ]);

            // Verifica credenciais
            if (!Auth::attempt($request->only('email', 'password'))) {
                Log::warning('Tentativa de login falhou - credenciais inválidas', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                throw ValidationException::withMessages([
                    'email' => ['As credenciais fornecidas estão incorretas.'],
                ]);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            Log::debug('Usuário encontrado, gerando token', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            // Revoga tokens antigos (opcional - para limitar sessões)
            // $tokensRevogados = $user->tokens()->count();
            // $user->tokens()->delete();
            // Log::debug("Tokens antigos revogados: {$tokensRevogados}");
            
            // Cria novo token com expiração customizada
            // Opção 1: Token sem expiração (padrão)
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Opção 2: Token com expiração (descomente para usar)
            // $expiresAt = now()->addDays(7); // 7 dias
            // $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_prefix' => substr($token, 0, 10) . '...'
            ]);

            // Retorna resposta com cookie HTTP-only
            return response()->json([
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token
            ])->cookie(
                'auth_token',                           // name
                $token,                                 // value
                config('session.lifetime', 120),        // minutes
                '/',                                    // path
                config('session.domain'),               // domain
                config('session.secure_cookie', false), // secure
                true,                                   // httpOnly
                false,                                  // raw
                'lax'                                   // sameSite
            );

        } catch (ValidationException $e) {
            Log::warning('Falha na validação do login', [
                'email' => $request->email,
                'errors' => $e->errors(),
                'ip' => $request->ip()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('Erro crítico durante login', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao realizar login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout do usuário
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('Tentativa de logout', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Deleta o token atual
            $request->user()->currentAccessToken()->delete();

            Log::info('Logout realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Remove o cookie
            return response()->json([
                'message' => 'Logout realizado com sucesso'
            ])->cookie(
                'auth_token',                           // name
                '',                                     // value
                -1,                                     // minutes (expira imediatamente)
                '/',                                    // path
                config('session.domain'),               // domain
                config('session.secure_cookie', false), // secure
                true,                                   // httpOnly
                false,                                  // raw
                'lax'                                   // sameSite
            );

        } catch (\Exception $e) {
            Log::error('Erro ao realizar logout', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao realizar logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna dados do usuário autenticado
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            Log::debug('Consulta de dados do usuário autenticado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'user' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados do usuário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar dados do usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza/renova o token do usuário
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('Renovação de token solicitada', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Deleta o token atual
            $request->user()->currentAccessToken()->delete();

            // Cria novo token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Token renovado com sucesso', [
                'user_id' => $user->id,
                'token_prefix' => substr($token, 0, 10) . '...'
            ]);

            return response()->json([
                'message' => 'Token renovado com sucesso',
                'token' => $token
            ])->cookie(
                'auth_token',
                $token,
                config('session.lifetime', 120),
                '/',
                config('session.domain'),
                config('session.secure_cookie', false),
                true,
                false,
                'lax'
            );

        } catch (\Exception $e) {
            Log::error('Erro ao renovar token', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao renovar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}