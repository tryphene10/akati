<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use  Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $objUser = new User();
        $objUser->name = 'Administrateur';
        $objUser->surname = 'admin';
        $objUser->email = 'admin@domain.cm';
        $objUser->phone = '652221144';
        $objUser->password = Hash::make('12345678');
        $objUser->role_id = 1;
        $objUser->published   = 1;
        $objUser->generateReference();
        $objUser->generateAlias('Administrateur');
        if(!$objUser->save())
        {
            $this->command->info("Fail Seeded User: Administrateur");
        }else{
            $this->command->info("Seeded User: ". $objUser->name);
        }


    }
}
