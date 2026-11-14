<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * ApiResponse TRAIT INJECTION:
     * Namma create panna standard JSON envelope methods-ah ($this->successResponse, $this->errorResponse)
     * intha controller-kulla direct-ah use panna intha trait-ah use panrom.
     */
    use ApiResponse;

    /**
     * 1. USER REGISTRATION (POST /api/register)
     * 
     * Express.js equivalent:
     * router.post('/register', validate(registerSchema), async (req, res) => { ... })
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // $request->validated() 100% clean data (name, email, password) mattum tharum
        // User model-la 'password' => 'hashed' nu cast potathala, password automatic-ah Bcrypt aagidum!
        $user = User::create($request->validated());

        // Register aana odane user login aagi token edukka login($user) use panrom:
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, 'User registered successfully', 201);
    }

    /**
     * 2. USER LOGIN (POST /api/login)
     * 
     * Express.js equivalent:
     * const valid = await bcrypt.compare(req.body.password, user.password);
     * const token = jwt.sign({ id: user._id }, SECRET);
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Email and Password mattum edukrom
        $credentials = $request->validated();

        // 🌟 auth('api')->attempt():
        // 1. Intha email-la user irukaara nu DB-la thedum
        // 2. Password hash correct-ah nu Bcrypt compare pannum
        // 3. Match aana -> pudhu JWT Token string generate panni tharum!
        // 4. Thappa iruntha -> 'false' nu return pannum.
        if (! $token = auth('api')->attempt($credentials)) {
            return $this->errorResponse('Invalid email or password', 401);
        }

        return $this->respondWithToken($token, 'Login successful', 200);
    }

    /**
     * 3. GET CURRENT PROFILE (GET /api/me)
     * 
     * Express.js equivalent:
     * router.get('/me', verifyTokenMiddleware, (req, res) => res.json(req.user));
     */
    public function me(): JsonResponse
    {
        // Header-la vantha Bearer token-ah decode panni, antha user details-ah edukrom
        $user = auth('api')->user();

        // Koodave user-oda active subscription details-um load aagi vanthudum
        $user->load('activeSubscription.plan');

        return $this->successResponse($user, 'User profile retrieved successfully');
    }

    /**
     * 4. USER LOGOUT (POST /api/logout)
     * 
     * Express.js equivalent:
     * await redisClient.setEx(token, 3600, 'blacklisted');
     */
    public function logout(): JsonResponse
    {
        // 🌟 auth('api')->logout():
        // Current token-ah JWT Blacklist-kulla pottrum!
        // Ithaala intha token-ah vachu yaaravathu marubadiyum request anupuna 401 Unauthorized aagidum!
        auth('api')->logout();

        return $this->successResponse(null, 'Successfully logged out');
    }

    /**
     * HELPER METHOD: Standard Token Format
     * Token koodave Token Type, Expiry Seconds, and User Object-ah bundle panni anupum.
     */
    protected function respondWithToken(string $token, string $message, int $statusCode = 200): JsonResponse
    {
        return $this->successResponse([
            'access_token' => $token,
            'token_type'   => 'bearer',
            // Token expiry seconds: 60 minutes * 60 = 3600 seconds
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => auth('api')->user(),
        ], $message, $statusCode);
    }
}