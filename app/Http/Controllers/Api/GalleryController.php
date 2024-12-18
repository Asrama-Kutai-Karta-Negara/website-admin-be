<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'updated_at');

        $query = Gallery::query();

        if (in_array($sortBy, ['name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $galleries = $query->paginate($limit);

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_GALLERY, $galleries);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $gallery = Gallery::create($input);

            if (!$gallery) {
                return ApiResponse::error(sprintf(ErrorMessages::FAILED_CREATE_MODEL, 'gallery'), 404);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_CREATE_GALLERY, $gallery, 201);
        } catch (\Exception $e) {
            Log::error('Gallery creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Gallery'), 404);
        }

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_GALLERY, $gallery);
    }

    public function showFile($id)
    {
        $gallery = Gallery::find($id);

        if (!$gallery) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Gallery'), 404);
        }

        try {
            // Convert file dari Base64 ke byte array
            $fileContent = base64_decode($gallery->file);

            if ($fileContent === false) {
                return ApiResponse::error('Invalid Base64 file data', 400);
            }

            // Tentukan MIME type, bisa Anda sesuaikan dengan tipe file yang ada
            $mimeType = Storage::mimeType($gallery->file);

            // Kembalikan file dengan response dan konten tipe sesuai MIME
            return response($fileContent, Response::HTTP_OK)
                ->header('Content-Type', $mimeType);
        } catch (\Exception $e) {
            Log::error('Error retrieving file: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|string|regex:/^([A-Za-z0-9+/=]\s*)*$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $gallery = Gallery::find($id);

        if (!$gallery) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Gallery'), 404);
        }

        try {
            $input = $request->only(['name', 'file', 'description']);


            $gallery->update(array_filter($input, function ($value) {
                return !is_null($value);
            }));

            $gallery->update($input);

            return ApiResponse::success(SuccessMessages::SUCCESS_UPDATE_GALLERY, $gallery);
        } catch (\Exception $e) {
            Log::error('Gallery creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $galerry = Gallery::find($id);

        if (!$galerry) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Galerry'), 404);
        }

        $galerry->delete();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_GALLERY);
    }
}
