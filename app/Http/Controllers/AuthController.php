<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
 
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
            // if (!$user = auth()->parseToken()->authenticate()) {
                    return response()->json(['user_not_found'], 404);
            }
          } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                  return response()->json(['token_expired'], $e->getStatusCode());
          } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                  return response()->json(['token_invalid'], $e->getStatusCode());
          } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
                  return response()->json(['token_absent'], $e->getStatusCode());
          }
          return response()->json(compact('user'));
        // return response()->json(auth()->user());
    }


    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh (Request $request)
	{
		try {
			$token = auth()->refresh();
			return response()->json(
				[
					'success' => true, 
					'data' => 
						[
							'token' => $token,
							'token_type' => 'bearer', 
							'expires_in' => auth()->factory()->getTTL() * 60
						]
				], 200
			);
		} catch (Exception $ex) {
			Log::error('Error API refrest()', ['usuario' => 'usuario1', 'companyId' => 'companyID', 'params' => $request, 'stackTrace' => $ex]);
			return response()->json(
				[
					'success' => false, 
					'message' => 'Ocurrió un error al refrescar sesión'
				]);
		}
	}


    public function refresh2()
    {
        return $this->respondWithToken(auth()->refresh());
    }


    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(),400);
        }

        $user = User::create(array_merge(
            $validator->validate(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => '¡Usuario registrado exitosamente!',
            'user' => $user
        ], 201);
    }

}