<?php

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Admin',
                'email' => 'admin@email.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
            ]
        ];

        $this->table('users')->insert($data)->save();
    }
}