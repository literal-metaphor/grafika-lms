<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\GenericCrudService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;

class ClassroomController extends Controller
{
    private $crudService;

    public function __construct()
    {
        $this->crudService = new GenericCrudService(new Classroom());
    }

    /**
     * Store a new photo of a classroom.
     * @param \Illuminate\Http\UploadedFile $originalPhoto
     * @return string
     */
    private function storePhoto(UploadedFile $originalPhoto)
    {

        $photo = ImageManager::gd()
            ->read($originalPhoto->getRealPath())
            ->resize(800, 600)
            ->encode(new WebpEncoder(50));

        $photopath = 'classroom/' . explode('.', $originalPhoto->hashName())[0] . '.webp';

        // Upload the file and persist the filepath
        $storedPhoto = Storage::disk('public')->put($photopath, $photo);
        if (!$storedPhoto) {
            abort(500, "Ada kesalahan saat mengunggah gambar");
        }

        return $photopath;
    }

    /**
     * Safely unlink a classroom photo.
     * @param \App\Models\Classroom $classroom
     * @return void
     */
    private function safeUnlinkPhoto(Classroom $classroom)
    {
        if (!$classroom->photo_path)
            return;
        Storage::disk('public')->delete($classroom->photo_path);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $req)
    {
        return response($this->crudService->index(
            $req->query('page'),
            $req->query('size'),
            ['subject']
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req)
    {
        $data = $req->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required'
        ]);

        // Check if photo is in the request
        if ($req->has('photo')) {
            $data['photo_path'] = $this->storePhoto($req->file('photo'));
        }

        $this->crudService->store(
            $data,
            [
                'subject_id' => 'required|exists:subjects,id',
                'name' => 'required',
                'photo_path' => 'nullable',
            ]
        );

        return response(
            [
                'message' => 'Classroom created successfully'
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response(
            $this->crudService->show($id, ['subject'])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        $data = $req->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required',
        ]);

        $classroom = $this->crudService->show($id);
        
        if ($req->has('photo')) {
            
            if ($classroom->photo_path) {
                $this->safeUnlinkPhoto($classroom);
            }

            $data['photo_path'] = $this->storePhoto($req->file('photo'));

        }
        
        $classroom->updateOrFail($data);

        return response([
            'message' => 'Classroom updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->safeUnlinkPhoto($this->crudService->show($id));

        return response(
            $this->crudService->destroy($id),
            204
        );
    }
}
