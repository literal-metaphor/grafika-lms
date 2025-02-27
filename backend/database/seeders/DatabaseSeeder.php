<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Coordinator;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private $subjects = [
        // Agama
        'islam',
        'kristen',
        'katolik',
        'hindu',
        'konghucu',
        'buddha',

        // Umum
        'ppkn',
        'bahasa_indonesia',
        'bahasa_daerah',
        'pjok',
        'sejarah',
        'seni',
        'bahasa_inggris',
        'matematika',

        // Vokasi
        'tg',
        'tkj',
        'rpl',
        'animasi',
        'dkv',
        'logistik',
        'mekatronika',
        'perhotelan',
    ];

    private function seedUser(string $type, int $iteration = 1) {
        $users = [];

        for ($i = 0; $i < $iteration; $i++) {
            $baseUser = User::create([
                'email' => fake()->safeEmail(),
                'name' => fake()->userName(),
                'password' => 'password'
            ]);

            switch ($type) {
                case 'student':
                    $users[] = Student::create([
                        'nis' => (string)fake()->numberBetween(100000000000, 999999999999),
                        'user_id' => $baseUser->id,
                    ]);
                    break;
                case 'teacher':
                    $users[] = Teacher::create([
                        'nip' => (string)fake()->numberBetween(100000000000, 999999999999),
                        'user_id' => $baseUser->id,
                    ]);
                    break;
                case 'coordinator':
                    $users[] = Coordinator::create([
                        'nip' => (string)fake()->numberBetween(100000000000, 999999999999),
                        'user_id' => $baseUser->id,
                        'subject_id' => fake()->randomElement($this->subjects)
                    ]);
                    break;
                case 'admin':
                    $users[] = Admin::create([
                        'nip' => (string)fake()->numberBetween(100000000000, 999999999999),
                        'user_id' => $baseUser->id,
                    ]);
                    break;
                
                default:
                    throw new \Exception("Undefined user type");
            }
        }

        return $users;
    }

    private function seedSubjects() {
        foreach ($this->subjects as &$sf) {
            $field = Subject::create([
                'name' => $sf
            ]);

            $sf = $field->id;

            unset($sf);
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedSubjects();

        $this->seedUser('student');
        $this->seedUser('teacher');
        $this->seedUser('coordinator');
        $this->seedUser('admin');
    }
}
