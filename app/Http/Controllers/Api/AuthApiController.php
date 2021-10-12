<?php

namespace App\Http\Controllers\Api;

use App\Notifications\AdminRegisteredUser;
use App\Role;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{


    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] expires_at
     */

    public function login(Request $request)
    {

        $this->_fnErrorCode = "01";
        $validator = Validator::make($request->all(), [
            'email'=>'required|email|max:'.Config::get('constants.size.max.email').'|exists:users,email',
            'password'=>'required|min:'.Config::get('constants.size.min.password').'|max:'.Config::get('constants.size.max.password')

        ]);

        if ($validator->fails())
        {
            if (!empty($validator->errors()->all()))
            {
                foreach ($validator->errors()->all() as $error)
                {
                    $this->_response['message'][] = $error;
                }
            }
            $this->_errorCode = 2;
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }


        $objUser = User::where('email', $request->get('email'))
            ->first();
        if(empty($objUser) || !$objUser->isPublished())
        {
            $this->_errorCode               = 2;
            $this->_response['message'][]   = trans('auth.denied');
            $this->_response['error_code']   = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        $arrPasswordValidation = $objUser->validatePassword($request->get('password'));
        if($arrPasswordValidation['success'] == false)
        {
            $this->_errorCode               = 5;
            $this->_response['message'][]   = trans('messages.login.fail.default');
            $this->_response['error_code']   = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        try
        {
            $objToken = $objUser->createToken('PersonalAccessToken');
        }
        catch(Exception $objException)
        {

            $this->_errorCode             = 6;
            if(in_array($this->_env, ['local', 'development']))
            {
                $this->_response['message'][]   = $objException->getMessage();
            }
            $this->_response['message'][]   = trans('messages.token.fail.generate');
            $this->_response['error_code']   = $this->prepareErrorCode();
            return response()->json($this->_response);
        }


        $toReturn = [
            'token'=>$objToken->accessToken,
            'ref_connected_user'=>$objUser->ref,
            'token_type'=>'Bearer',
            'infos' => $objUser
        ];
        $this->_response['data']    = $toReturn;
        $this->_response['success'] = true;

        return response()->json($this->_response);
    }





    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $objUser            = Auth::user();
        $this->_fnErrorCode = "02";

        if(empty($objUser))
        {
            $this->_errorCode = 6;
            $this->_response['error_code'] = $this->prepareErrorCode();
            $this->_response['message'][]   = Lang::get('messages.error-occured.default');
            return response()->json($this->_response);

        }

        $request->user()->token()->revoke();

        $arrResult[] = [
            'message'=>Lang::get('logged-out')
        ];
        $this->_response['success'] = true;
        $this->_response['data'] = [
            'result'=>$arrResult
        ];

        return response()->json($this->_response);
    }

    // Afficher la liste des users
    public function ListeUser()
    {
        $this->_errorCode  = 5;
        $toReturn = [
            'users'=>User::all(),
        ];
        $this->_response['data']    = $toReturn;
        $this->_response['success'] = true;
        return response()->json($this->_response);
    }

    public function update(Request $request){
        $this->_fnErrorCode = "01";
        $validator = Validator::make($request->all(), [
            'user'=>'string|required',
            'role'=>'nullable',
            'nom'=>'nullable',
            'prenom'=>'nullable',
            'phone'=>"nullable",
            'email'=>"nullable",
            'password'=>'nullable|min:'.Config::get('constants.size.min.password').'|max:'.Config::get('constants.size.max.password'),
        ]);

        if ($validator->fails()){
            if (!empty($validator->errors()->all())){
                foreach ($validator->errors()->all() as $error){
                    $this->_response['message'][] = $error;
                }
            }
            $this->_errorCode = 2;
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }
        $objUser = Auth::user();
        if(empty($objUser)){
            $this->_errorCode = 3;
            $this->_response['message'][] = "Cette action nécéssite une connexion.";
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }
        $objAuthRole = Role::where("id", $objUser->role_id)->first();
        if(empty($objAuthRole)){
            DB::rollback();
            $this->_errorCode = 4;
            $this->_response['message'][] = "Le user n'a pas de rôle.";
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }
        DB::beginTransaction();

        try{
            $objUpdateUser = User::where('ref', '=', $request->get('user'))->first();
            if(empty($objUpdateUser)){
                DB::rollback();
                $this->_errorCode = 5;
                $this->_response['message'][] = "L'utilisateur n'existe pas";
                $this->_response['error_code'] = $this->prepareErrorCode();
                return response()->json($this->_response);
            }




            if($request->has('nom') && $request->get('nom')!=""){
                if(!$objUpdateUser->update(["name" => $request->get('nom')])){
                    DB::rollback();
                    $this->_errorCode = 10;
                    $this->_response['message'][] = "La modification n'a pas été éffectué.";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }
            }

            if($request->has('prenom') && $request->get('prenom')!=""){
                if(!$objUpdateUser->update(["surname" => $request->get('prenom')])){
                    DB::rollback();
                    $this->_errorCode = 11;
                    $this->_response['message'][] = "La modification n'a pas été éffectué.";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }
            }

            if($request->has('phone') && $request->get('phone')!=""){
                if(!$objUpdateUser->update(["phone" => $request->get('phone')])){
                    DB::rollback();
                    $this->_errorCode = 12;
                    $this->_response['message'][] = "La modification n'a pas été éffectué.";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }
            }

            if($request->has('password') && $request->get('password')!=""){
                if(!$objUpdateUser->update(["password" => Hash::make($request->get('password'))])){
                    DB::rollback();
                    $this->_errorCode = 14;
                    $this->_response['message'][] = "La modification n'a pas été éffectué.";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }
            }

            if($request->has('role') && $request->get('role')!=""){
                $objUserRole = Role::where('ref', '=', $request->get('role'))->first();
                if(empty($objUserRole)){
                    DB::rollback();
                    $this->_errorCode = 15;
                    $this->_response['message'][] = "Le 'Rôle' n'existe pas";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }

                try{
                    $objUpdateUser->update(["role_id" => $objUserRole->id]);
                }catch (Exception $objException){
                    DB::rollback();
                    $this->_errorCode = 16;
                    if(in_array($this->_env, ['local', 'development'])){
                    }
                    $this->_response['message'] = $objException->getMessage();
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json( $this->_response );
                }
            }


        }catch (Exception $objException){
            DB::rollback();
            $this->_errorCode = 21;
            if(in_array($this->_env, ['local', 'development'])){
            }
            $this->_response['message'] = $objException->getMessage();
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json( $this->_response );
        }
        DB::commit();

        //Format d'affichage de message
        $toReturn = [
            'objet' => $objUpdateUser,
        ];

        $this->_response['message'] = 'Modification réussi!';
        $this->_response['data'] = $toReturn;
        $this->_response['success'] = true;
        return response()->json($this->_response);
    }

    public function delete(Request $request)
    {
        $this->_fnErrorCode = "01";
        $validator = Validator::make($request->all(), [
            'user'=>'required'
        ]);

        if ($validator->fails()){
            if (!empty($validator->errors()->all())){
                foreach ($validator->errors()->all() as $error){
                    $this->_response['message'][] = $error;
                }
            }
            $this->_errorCode = 2;
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        $objUser = Auth::user();
        if(empty($objUser)){
            $this->_errorCode = 3;
            $this->_response['message'][] = "Cette action nécéssite une connexion.";
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        $objAuthRole = Role::where('id', '=', $objUser->role_id)->first();
        if(empty($objAuthRole)){
            $this->_errorCode = 4;
            $this->_response['message'][] = "L'utilisateur n'a pas de rôle.";
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        $auth_01 = array("administrateur");
        if($request->has("user")){
            if(in_array($objAuthRole->alias, $auth_01)){
                $objDelUser = User::where("ref", $request->get("user"));
                if(!$objDelUser->update(["published" => 1])){
                    DB::rollback();
                    $this->_errorCode = 5;
                    $this->_response['message'][] = "La suppression a échoué.";
                    $this->_response['error_code'] = $this->prepareErrorCode();
                    return response()->json($this->_response);
                }
            }else{
                DB::rollback();
                $this->_errorCode = 6;
                $this->_response['message'][] = "Vous n'étes pas habilié.";
                $this->_response['error_code'] = $this->prepareErrorCode();
                return response()->json($this->_response);
            }

        }else{
            $this->_errorCode = 7;
            $this->_response['message'][] = "User n'existe pas.";
            $this->_response['error_code'] = $this->prepareErrorCode();
            return response()->json($this->_response);
        }

        $toReturn = [
            'objet' => $objDelUser
        ];

        $this->_response['message'] = "L'utilisateur a été supprimé!";
        $this->_response['data'] = $toReturn;
        $this->_response['success'] = true;
        return response()->json($this->_response);
    }

}
