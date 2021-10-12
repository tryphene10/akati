<?php

use App\Categorie;
use Illuminate\Database\Seeder;
use App\Role;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $objRole = new Role();
        $objRole->name = 'Administrateur';
        $objRole->published   = 1;
        $objRole->generateReference();
        $objRole->generateAlias('Administrateur');
        $objRole->save();
        if(!$objRole->save())
        {
            $this->command->info("Fail Seeded Role: Administrateur");
        }else{
            $this->command->info("Seeded Role: ". $objRole->name);
        }



    }
}
