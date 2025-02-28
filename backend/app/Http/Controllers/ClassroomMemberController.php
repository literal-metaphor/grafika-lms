<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Classroom;
use App\Models\UserClassroom;
use App\Services\UserSchemaResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ClassroomMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $req, Classroom $classroom)
    {
        return response(
            UserClassroom::with('user')->where('classroom_id', $classroom->id)->get()
                ->map(
                    function ($member) {
                        $member->user = UserSchemaResolver::resolveFromUserModel($member->user);
                        return $member;
                    }
                )
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req, Classroom $classroom)
    {
        /** @var User $user */
        $user = User::find($req->query('userId'));

        if ($user) {
            $user = UserSchemaResolver::resolveFromUserModel($user);
        }

        // Since adding new member by ID is impractical, we'll allow implicit NIP or NIS resolution
        if (!$user) {
            if ($req->query('nip')) {
                $user = UserSchemaResolver::resolveFromNip($req->query('nip'));
            }
            if ($req->query('nis')) {
                $user = UserSchemaResolver::resolveFromNis($req->query('nis'));
            }
        }

        if (!$user) {
            throw new ModelNotFoundException("User not found");
        }

        if (
            UserClassroom::where(
                'classroom_id',
                $classroom->id
            )->where('user_id', $user->id)->first()
        ) {
            throw new ConflictHttpException('Anggota kelas sudah ada');
        }

        UserClassroom::create([
            'classroom_id' => $classroom->id,
            'user_id' => $user->id
        ]);

        return response([
            'message' => 'Anggota kelas berhasil ditambahkan'
        ]);
    }

    /**
     * Display the specified resource.
     */
    // !I don't think we'll have a need for showing a specific membership
    // public function show(string $id)
    // {

    // }

    /**
     * Update the specified resource in storage.
     */
    // !I don't think we'll have a need for updating membership, just delete and create should be enough
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $req, Classroom $classroom, User $user)
    {
        $membership = UserClassroom::where(
            'classroom_id', $classroom->id
        )->where('user_id', $user->id)->firstOrFail();

        $membership->delete();

        return response(null, 204);
    }
}
