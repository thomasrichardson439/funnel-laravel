<?php

namespace App\Http\Controllers\API\v1\Authentication;

use App\Http\Requests\Api\Users\StoreUserRequest;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    use RegistersUsers;

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => 'required|string|max:255',
            'email'    => 'required_without:password|email|unique:users,email',
            'phone_number'    => 'required_without:email|unique:users,phone_number',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="register for the system",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="password",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_number",
     *                     type="string"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *      response="200",
     *      description="Access token information",
     *   )
     *  )
     * )
     * @param StoreUserRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    protected function create(StoreUserRequest $request)
    {

        DB::beginTransaction();

        $user = new User(); 
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_number  = $request->phone_number;
        $user->password = Hash::make($request->password);
        $user->save();

        if (\App::environment(['local', 'staging'])) {
            $user->update([
                'verified' => true,
                'email_verified_at' => Carbon::now()
            ]);
        } else {
            $user->sendVerification();
        }

        $response = ['access_token' => $user->generateToken()];

        DB::commit();

        return $this->sendResponse(
            $response, 201
        );
    }

}
