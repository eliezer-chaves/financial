# Laravel Sanctum - Autenticação JWT

Este projeto utiliza Laravel Sanctum para autenticação baseada em tokens JWT com suporte a cookies HttpOnly para SPAs (Single Page Applications).

## 📋 Pré-requisitos

- PHP >= 8.1
- Composer
- MySQL/PostgreSQL ou outro banco de dados
- Node.js (para o frontend)

## 🚀 Instalação e Configuração

### 1. Clonar o Repositório

```bash
git clone <url-do-repositorio>
cd nome-do-projeto
```

### 2. Instalar Dependências

```bash
composer install
```

### 3. Configurar o Ambiente

Copie o arquivo de exemplo e configure:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar o Banco de Dados

Edite o arquivo `.env` com as credenciais do seu banco de dados:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_do_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 5. Configurar Sessões e Sanctum

Adicione/edite as seguintes variáveis no arquivo `.env`:

```env
# Configurações de Sessão
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false  # Alterar para true em produção com HTTPS

# Domínios autorizados para requisições stateful (com cookies)
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000

# URL do Frontend (SPA)
SPA_URL=http://localhost:3000
```

**Importante para Produção:**
```env
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=seudominio.com,www.seudominio.com
SPA_URL=https://seudominio.com
```

### 6. Configurar CORS

Edite o arquivo `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [env('SPA_URL', 'http://localhost:3000')],

'allowed_methods' => ['*'],

'allowed_headers' => ['*'],

'exposed_headers' => [],

'max_age' => 0,

'supports_credentials' => true, // IMPORTANTE: deve ser true
```

### 7. Configurar o Sanctum Middleware

No arquivo `bootstrap/app.php` (Laravel 11+) ou `app/Http/Kernel.php` (Laravel 10), certifique-se de que o middleware está configurado:

**Laravel 11:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();
})
```

**Laravel 10:**
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 8. Executar as Migrations

```bash
php artisan migrate
```

### 9. Configurar o Model User

Adicione o trait `HasApiTokens` no model `User`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // ... resto do código
}
```

## 🔐 Configuração de HttpOnly Cookies

### ⚠️ Resposta à sua dúvida: Onde configurar HttpOnly?

**O HttpOnly é configurado NO LARAVEL (backend), NÃO no frontend.**

O Laravel Sanctum já configura automaticamente os cookies como HttpOnly quando você usa autenticação stateful (baseada em sessão). Isso acontece através do middleware `EnsureFrontendRequestsAreStateful`.

**Por que no backend?**
- Cookies HttpOnly não podem ser acessados via JavaScript (por segurança)
- Apenas o servidor pode definir e ler cookies HttpOnly
- O frontend apenas envia o cookie automaticamente nas requisições

**Configuração automática pelo Sanctum:**
```php
// O Sanctum configura automaticamente:
// - HttpOnly: true
// - SameSite: lax
// - Secure: true (em produção com HTTPS)
```

## 📡 Implementação da API

### Rotas de Autenticação

Crie as rotas no arquivo `routes/api.php`:

```php
use App\Http\Controllers\Auth\AuthController;

// Rota pública para obter o CSRF token
Route::get('/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Rotas de autenticação
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
```

### Controller de Autenticação

Crie o controller:

```bash
php artisan make:controller Auth/AuthController
```

Exemplo de implementação:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Cria um token de acesso
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

## 🌐 Configuração do Frontend

### Requisições com Axios (React/Vue/Angular)

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000',
    withCredentials: true, // IMPORTANTE: permite envio de cookies
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// 1. Primeiro, obter o CSRF cookie
await api.get('/sanctum/csrf-cookie');

// 2. Fazer login
const response = await api.post('/api/login', {
    email: 'user@example.com',
    password: 'password'
});

// 3. Usar o token nas próximas requisições
api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

// 4. Fazer requisições autenticadas
const user = await api.get('/api/user');
```

### Exemplo Completo (React)

```javascript
// services/auth.js
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000',
    withCredentials: true
});

export const login = async (email, password) => {
    // Obter CSRF token
    await api.get('/sanctum/csrf-cookie');
    
    // Fazer login
    const response = await api.post('/api/login', { email, password });
    
    // Salvar token no localStorage
    localStorage.setItem('token', response.data.token);
    
    return response.data;
};

export const getUser = async () => {
    const token = localStorage.getItem('token');
    
    const response = await api.get('/api/user', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    return response.data;
};
```

## 🧪 Testando a API

### Com cURL

```bash
# 1. Obter CSRF cookie
curl -X GET http://localhost:8000/sanctum/csrf-cookie -c cookies.txt

# 2. Login
curl -X POST http://localhost:8000/api/login \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# 3. Obter usuário autenticado
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 🔒 Segurança

### Boas Práticas

1. **Produção**: sempre use HTTPS (`SESSION_SECURE_COOKIE=true`)
2. **CORS**: configure domínios específicos em produção
3. **Rate Limiting**: configure throttle nas rotas
4. **Validação**: sempre valide dados de entrada
5. **Tokens**: defina expiração adequada para tokens

### Rate Limiting

```php
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
```

## 🚀 Comandos Úteis

```bash
# Limpar cache de configuração
php artisan config:clear

# Limpar cache de rotas
php artisan route:clear

# Rodar servidor de desenvolvimento
php artisan serve

# Criar nova migration
php artisan make:migration create_nome_tabela

# Ver todas as rotas
php artisan route:list
```

## 📚 Referências

- [Documentação Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Documentação Laravel CORS](https://laravel.com/docs/routing#cors)
- [MDN - HTTP Cookies](https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Cookies)

## 📝 Notas Importantes

- **HttpOnly cookies** são gerenciados automaticamente pelo Laravel
- O frontend só precisa configurar `withCredentials: true` no axios
- Em produção, sempre use HTTPS para segurança dos cookies
- O token JWT pode ser armazenado no localStorage ou enviado automaticamente via cookies