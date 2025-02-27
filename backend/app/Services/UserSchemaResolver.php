<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Coordinator;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UserSchemaResolver
{
    protected static $models = [
        'student' => Student::class,
        'teacher' => Teacher::class,
        'coordinator' => Coordinator::class,
        'admin' => Admin::class,
    ];

    /**
     * Resolve user schema from NIS
     * @param string $nis
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     * @return User
     */
    public static function resolveFromNis(string $nis) {
        // Try to find student based on NIS
        $student = Student::where('nis', $nis)->first();
        if (!$student) {
            throw new ModelNotFoundException("NIS tidak ditemukan");
        }
        
        // Try to find user based on student user_id
        /** @var User */
        $user = User::find($student->user_id);
        if (!$user) {
            throw new ConflictHttpException('NIS tanpa user ditemukan, mohon hubungi admin');
        }
        
        // Merge student and user data, then return
        $user->student = $student;
        $user->role = 'student';
        return $user;
    }

    /**
     * Resolve user schema from NIP
     * @param string $nip
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @return User
     */
    public static function resolveFromNip(string $nip) {
        $models = [
            'teacher' => Teacher::class,
            'coordinator' => Coordinator::class,
            'admin' => Admin::class,
        ];

        foreach ($models as $role => $modelClass) {
            if ($model = $modelClass::where('nip', $nip)->first()) {
                /** @var User */
                $user = User::find($model->user_id);
                if (!$user) {
                    throw new ConflictHttpException('NIP tanpa user ditemukan, mohon hubungi admin');
                }

                $user->{$role} = $model;
                $user->role = $role;
                return $user;
            }
        }

        throw new ModelNotFoundException('NIP tidak ditemukan');
    }

    /**
     * Resolve complete user schema and role from user model
     * @param \App\Models\User $user
     * @throws \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     * @return User
     */
    public static function resolveFromUserModel(User $user) {
        foreach (self::$models as $role => $modelClass) {
            if ($model = $modelClass::where('user_id', $user->id)->first()) {
                $user->{$role} = $model;
                $user->role = $role;
                return $user;
            }
        }

        throw new ConflictHttpException('User tanpa role ditemukan, mohon hubungi admin');
    }

    /**
     * Perform a callback on the user actor model based on user role
     * @param \App\Models\User $user
     * @param callable $fn
     * @return void
     */
    public static function callbackOnUserActor(User $user, callable $fn) {
        foreach (self::$models as $role => $modelClass) {
            if ($user->role === $role) {
                $fn($modelClass);
                return;
            }
        }
    }
}
