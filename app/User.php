<?php

namespace App;

use App\Helpers\CustFunc;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasApiTokens , Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'surname',
        'phone',
        'email',
        'password',
        'published',
        'activation_date'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /*
   * Convert dates to Carbon.
   *
   */

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function validatePassword($value)
    {
        $toReturn = [
            'success'=>false,
            'message'=>""
        ];
        if(empty($this->attributes['password']))
        {
            $toReturn['message']    = trans('messages.password.invalid.empty');
            return $toReturn;
        }
        if(!Hash::check($value, $this->attributes['password']))
        {
            $toReturn['message']    = trans('messages.password.invalid.default');
            return $toReturn;
        }

        $toReturn['success'] = true;
        return $toReturn;
    }


    public function isPublished()
    {
        return $this->published;
    }


    public function user(){
        return $this->belongsTo('App\User','user_id');
    }
    /*
         * Children relationship
         */
    public function role(){
        return $this->belongsTo('App\Role');
    }


    public function generateAlias($name)
    {
        $append = Config::get('constants.values.zero');
        if(empty($this->attributes['alias']))
        {
            do
            {
                if($append == Config::get('constants.values.zero'))
                {
                    $alias = CustFunc::toAscii($name);
                }
                else
                {
                    $alias = CustFunc::toAscii($name)."-".$append;
                }

                $append   += 1;
            }
            while
            (
                User::where('alias',$alias)
                    ->first()
                instanceof User
            );

            $this->attributes['alias'] = $alias;
        }
    }

    public function generateReference()
    {

        if(empty($this->attributes['ref']))
        {
            do
            {
                $token = CustFunc::getToken(Config::get('constants.size.ref.user'));
            }
            while
            (
                User::where('ref',$token)
                    ->first()
                instanceof User
            );

            $this->attributes['ref'] = $token;

            return true;
        }
        return false;
    }
}
