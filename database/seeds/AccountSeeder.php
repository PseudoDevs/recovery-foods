<?php

use App\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::insert(
            [
                ['id' => 1, 'name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'admin@gmail.com', 'password' => '$2y$10$tpKw/GNFefG8Ah2hBNbTRuIFIxWXdeY1BwXzVOMHylRVpR2U6lu7.', 'phone_number' => '(639) 123-4567', 'branch' => '5', 'remember_token' => ''],
                ['id' => 2, 'name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'cashier@gmail.com', 'password' => '$2y$10$E5BfxyCY826Hz5CSrIiCA.sykE3K4/O3rqA83DMI2EzGn6Ki3RPdy', 'phone_number' => '(0945) 384-0702', 'branch' => '1', 'remember_token' => ''],
                ['id' => 3, 'name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'supervisor@gmail.com', 'password' => '$2y$10$RVswqeP1qKNDk17yJ.n/luxNO1VT2OKekJO3DjWdOINFaoGPHPOq6', 'phone_number' => '(123) 456-789', 'branch' => '1', 'remember_token' => ''],
                ['id' => 4, 'name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'stockman@gmail.com', 'password' => '$2y$10$s7K0shSdyMFqNDhr/iFpP.dZ4rCAJIHt9YneODEbUuamCRYoVi0IS', 'phone_number' => '(123) 456-789', 'branch' => '5', 'remember_token' => ''],
                ['id' => 5, 'name' => 'John Doe', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'prodassistant@gmail.com', 'password' => '$2y$10$6Snv0Bgyr0iRighKmL2Fb.3HmPBQM.siT1ROiTfbeRrOSfCejsiVe', 'phone_number' => '(123) 456-789', 'branch' => '5', 'remember_token' => ''],
            ]
        );
    }
}
