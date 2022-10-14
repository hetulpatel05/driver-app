<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\Mail;
use JWTAuth;


use App\Models\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function login(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [               
                'phone' => 'required |numeric',             
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);
        }

        $user = User::select('id')->where('phone', '=', $request->phone)->first();

        try {
            // verify the credentials and create a token for the user
            if (!$token = JWTAuth::fromUser($user)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong 
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(['success'=>true,'message'=>'login successfully','token_type'=>'bearer','token'=>$token]);


    }

    public function registerUser(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required | max:15',
                'phone' => 'required | numeric|unique:users,phone',
                'email' => 'required|email',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['result' => 0, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);
        }

        $newuser = new User;
        $newuser->name = $request->name;
        $newuser->phone = $request->phone;
        $newuser->email = $request->email;        
        $newuser->save();

        if($newuser==true)
        {
            return response()->json(['success'=>true,'message'=>'User Registered Successfully.']);
        }
    }

    public function updateProfile(Request $request)
    {
        $user_id=Auth::user()->id;

        $validator = Validator::make($request->all(),
                [
                    'name' => 'required',                                
                    'email' => 'required|unique:users,email,'.$user_id
                ]);

            if ($validator->fails())
            {
                return response()->json([ 'success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);            
            }

        $save_profile=User::where('id', $user_id)
                ->update(['name' => $request->name,
                          'email'=>$request->email,
                        ]);

        if($save_profile=true)
        {
            return response()->json(['success'=>true,'message'=>'User Profile Updated Successfull']); 
        }
    }
}