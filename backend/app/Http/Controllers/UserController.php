<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Coordinator;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GenericCrudService;
use App\Services\UserSchemaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserController extends Controller
{
    private $crudService;

    public function __construct()
    {
        $this->crudService = new GenericCrudService(new User());
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        $paginatedUsers = $this->crudService->index(
            $req->query('page'),
            $req->query('size')
        );

        // Append user role and extended schema
        $paginatedUsers->data = array_map(function($user) {
            return UserSchemaResolver::resolveFromUserModel($user);
        }, $paginatedUsers->items());

        return response($paginatedUsers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:student,teacher,coordinator,admin',

            // Student must have NIS
            'nis' => 'required_if:role,student',
            // School staff must have NIP
            'nip' => 'required_if:role,teacher|required_if:role,coordinator|required_if:role,admin',

            // Coordinator must only be responsible for one subject
            'subject_id' => 'required_if:role,coordinator|exists:subjects,id'
        ]);

        // Create the base user
        $baseUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        // Create the user actor
        // TODO: Use UserSchemaResolver::callbackOnUserActor() instead
        switch ($data['role']) {
            case 'student':
                Student::create([
                    'user_id' => $baseUser->id,
                    'nis' => $data['nis'],
                ]);
                break;
            case 'teacher':
                Teacher::create([
                    'user_id' => $baseUser->id,
                    'nip' => $data['nip'],
                ]);
                break;
            case 'coordinator':
                Coordinator::create([
                    'user_id' => $baseUser->id,
                    'nip' => $data['nip'],
                    'subject_id' => $data['subject_id'],
                ]);
                break;
            case 'admin':
                Admin::create([
                    'user_id' => $baseUser->id,
                    'nip' => $data['nip'],
                ]);
                break;
            
            default:
                throw new UnprocessableEntityHttpException('Role user tidak valid.');
        }

        // Resolve user schema before returning result
        $user = UserSchemaResolver::resolveFromUserModel($baseUser);

        return response([
            'message' => 'User berhasil ditambahkan'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $req, string $id)
    {
        /** @var User $user */
        $user = $this->crudService->show($id);
        $user = UserSchemaResolver::resolveFromUserModel($user);

        return response($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        /** @var User $user */
        $user = $this->crudService->show($id);

        $data = $req->all();
        $data = validator($data, [
            'name' => 'nullable',
            'email' => 'nullable|email|unique:users,email',
            'role' => 'nullable|in:student,teacher,coordinator,admin',

            // Only student can have editable NIS
            'nis' => [
                Rule::when(request('role') === 'student', ['required', 'prohibited_unless:role,student'], ['prohibited']),
            ],
            // Only school staff can have editable NIP
            'nip' => [
                Rule::when(request('role') !== 'student', ['required', 'prohibited_if:role,student'], ['prohibited']),
            ],

            // Only coordinator can have editable subject
            'subject_id' => [
                Rule::when(request('role') === 'coordinator', ['required', 'exists:subjects,id'], []),
            ],
        ])->validate();

        $baseUserData = Arr::only($data, ['name', 'email']);
        $actorUserData = Arr::except($data, ['name', 'email', 'role']);

        // Update the base user
        $user->update($baseUserData);

        // Update the user actor
        $user = UserSchemaResolver::resolveFromUserModel($user);
        if (isset($data['role']) && $user->role !== $data['role']) {
            // Delete old actor
            UserSchemaResolver::callbackOnUserActor($user, function($actor) use ($user) {
                $actor::findOrFail($user->{$user->role}->id)->delete();
            });

            // Create new actor
            $user->role = $data['role'];
            $actorUserData['user_id'] = $user->id;
            UserSchemaResolver::callbackOnUserActor($user, function($actor) use ($actorUserData) {
                $actor::create($actorUserData);
            });
        } else {
            // Update the user's current actor
            UserSchemaResolver::callbackOnUserActor($user, function($actor) use ($user, $actorUserData) {
                $actor::findOrFail($user->{$user->role}->id)->update($actorUserData);
            });
        }

        return response([
            'message' => 'User berhasil diperbarui'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $req, string $id)
    {
        return response(
            $this->crudService->destroy($id),
            204
        );
    }
}
