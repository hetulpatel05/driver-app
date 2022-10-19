<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Support\Facades\Mail;
use JWTAuth;
use Illuminate\Support\Facades\File;


use App\Models\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function login(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [               
                'phone' => 'required | numeric|unique:users,phone',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);
        }

        $user = User::select('id')->where('phone',$request->phone)->first();
        $otp=rand(0000,9999);
        if(!empty($user))
        {
            User::where('id',$user->id)->update(['otp'=>$otp]);
            $status=1;
            $msg="Phone Numbers is Authenticated";
        }
        else
        {
            $newuser = new User;            
            $newuser->phone = $request->phone;
            $newuser->otp = $otp;        
            $newuser->save();

            $status=2;
            $msg="New account created successfully";
        }

        return response()->json(['success'=>true,'status'=>$status,'message'=>$msg]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [               
                'phone' => 'required |numeric', 
                'otp_code' => 'required |numeric',
                'auth_type' => 'required |numeric',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);
        }
        
        $check_code = User::select('id')->where('phone',$request->phone)
        ->where('otp',$request->otp_code)
        ->first();
        
        if(!empty($check_code))
        {   
            $bearer_token=$this->generateToken($check_code);
            $auth_message=($request->auth_type==1)?'Direct Login':'New Account';
                        
            return response()->json(['success'=>true,'auth_type'=>$request->auth_type,'auth_message'=>$auth_message,'token'=>$bearer_token]);
        }
        else
        {
            return response()->json(['success'=>false,'message'=>'Entered OTP is not valid']);
        }

    }

    public function generateToken($user)
    {
        try {
            // verify the credentials and create a token for the user
            if (!$token = JWTAuth::fromUser($user)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong 
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return $token;
    }

    public function registerUser(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required | max:15',                
                'email' => 'email',
                'user_image' => 'image|mimes:jpeg,png,jpg|max:5120',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['result' => 0, 'message' => 'Validation Error', 'errors' => $validator->errors()->messages()]);
        }

        $userdetails=User::select('user_image')->where('phone',$request->phone)->first();

        $user_image=$request->user_image;
        $old_user_img=$userdetails->user_image;

        if($user_image!=null || $user_image !='')
        {
            if(!empty($old_user_img))
            {
                $old_image_path = "uploads/user_image/".$old_user_img;

                if(File::exists($old_image_path))
                {
                    File::delete($old_image_path);
                }                    
            }

            $imagename = strtolower(date('Ymd').'.'.$user_image->getClientOriginalExtension());
            $imagepath ='uploads/user_images/';
            $user_image->move($imagepath, $imagename);
        }
        else
        {
            $imagename=null;
        }

        $save_profile=User::where('phone', $request->phone)
                ->update(['name' => $request->name,
                          'email'=>$request->email,
                          'user_image'=>$imagename,
                        ]);      

        if($save_profile==true)
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