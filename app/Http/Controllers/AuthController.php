<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Hash;

class AuthController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService) {
        $this->responseService = $responseService;
    }

    public function register(Request $request) {

        $validator = Validator::make($request->all(), [
            'name'=>'required|min:2|max:100',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6|max:100',
            'confirm_password'=>'required|same:password'
        ]);

        if ($validator->fails()) {
            return $this->responseService->validationErrorResponse($validator->errors());
        }

        $user=User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);

        return $this->responseService->successResponse('Registration successful', [
            'data' => $user,
        ]);
    }

    public function login(Request $request) {

        $validator = Validator::make($request->all(), [
            'email'=>'required|email',
            'password'=>'required',
        ]);

        if ($validator->fails()) {
            return $this->responseService->validationErrorResponse($validator->errors());
        }

        $user=User::where('email',$request->email)->first();

        if ($user && Hash::check($request->password,$user->password)) {
            $token=$user->createToken('auth-token')->plainTextToken;

            return $this->responseService->successResponse('Login successful', [
                'token' => $token,
                'data' => $user,
            ]);
            
        }
        return $this->responseService->errorResponse('Incorrect credentials', 400);
    }

    public function user(Request $request) {

        return $this->responseService->successResponse('User successfully fetched', [
            'data' => $request->user(),
        ]);
    }

    public function logout(Request $request) {
        
        $request->user()->currentAccessToken()->delete();

        return $this->responseService->successResponse('User successfully logged out');
    }
}
