<?php

use Phinx\Seed\AbstractSeed;

class CustomerSeeder extends AbstractSeed
{
    public function run(): void
    {
        $existing = $this->fetchRow('SELECT COUNT(*) AS total FROM customers');
        if ((int) ($existing['total'] ?? 0) > 0) {
            return;
        }

        $firstNames = [
            'Lucas',
            'Mariana',
            'Pedro',
            'Ana',
            'Rafael',
            'Beatriz',
            'Gabriel',
            'Juliana',
            'Thiago',
            'Carolina',
            'Felipe',
            'Camila',
            'Joao',
            'Larissa',
            'Bruno',
            'Renata',
            'Diego',
            'Patricia',
            'Vinicius',
            'Fernanda',
        ];

        $lastNames = [
            'Silva',
            'Souza',
            'Oliveira',
            'Santos',
            'Lima',
            'Pereira',
            'Costa',
            'Almeida',
            'Gomes',
            'Ribeiro',
            'Martins',
            'Rocha',
            'Araujo',
            'Barbosa',
            'Nunes',
        ];

        $customers = [];

        for ($i = 1; $i <= 15; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $fullName = sprintf('%s %s', $firstName, $lastName);

            $emailSlug = strtolower($firstName . '.' . $lastName);
            $email = sprintf('%s%02d@customer.com', $emailSlug, $i);

            $ddd = mt_rand(11, 99);
            $prefix = mt_rand(90000, 99999);
            $suffix = mt_rand(1000, 9999);
            $phone = sprintf('(%d) %d-%d', $ddd, $prefix, $suffix);

            $customers[] = [
                'name' => $fullName,
                'email' => $email,
                'phone' => $phone,
            ];
        }

        $this->table('customers')->insert($customers)->saveData();
    }
}
