<?php

namespace App\Http\Controllers\API\v1\Authentication;

use App\Events\EmailVerified;
use App\Http\Controllers\Controller;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller {

    /**
     * @OA\Get(
     *     path="/api/v1/email/verify/{id}",
     *     summary="Post to email verification MUST contain the authorization bearer token in Headers. Simply, the User must be logged in to verify their identity.",
     *     @OA\Response(
     *      response="200",
     *      description="'verification': true"
     *  )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        if(!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
            ]);
        }

        if ($request->route('id') != $request->user()->getKey()) {
            throw new AuthorizationException;
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new EmailVerified($request->user()));
//            $request->user()->notify(new VerifyEmail($request->user()));
        }

        $request->user()->update([
            'verified' => true
        ]);

        return response()->json([
            'verification' => true
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sms/verify",
     *     summary="Post a 5 digit verification PIN, Headers MUST contain the authorization bearer token.",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="verification_code",
     *                     type="integer"
     *                 ),
     *                 example={"verification_code": 24465}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *      response="200",
     *      description="'verification': true",
     *   )
     *  )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verifySMS(Request $request)
    {
        if (Hash::check($request->verification_code, $request->user()->verification_code)) {
            $request->user()->update([
                'verified' => true
            ]);

            return response()->json([
                'verification' => true
            ]);
        }

        return response()->json([
            'verification' => false
        ]);
    }


}
