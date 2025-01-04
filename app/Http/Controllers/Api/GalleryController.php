<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\FileConstant;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\CategoryGallery;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', null);
        $sortBy = $request->input('sort_by', 'updated_at');
        $categoryId = $request->input('category_id');
        $name = $request->input('name');

        $maxLimit = 1000;
        $limit = is_numeric($limit) ? min((int)$limit, $maxLimit) : $maxLimit;

        $query = Gallery::query();

        if ($name) {
            $query->byTitle($name);
        }

        if (isset($categoryId)) {
            $query->filterByCategoryId($categoryId);
        }

        if (in_array($sortBy, ['name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $galleries = $query->paginate($limit);

        foreach ($galleries as $gallery) {
            $category = CategoryGallery::find($gallery->category_id);
            if ($category) {
                $gallery->category_name = $category->name;
            }
            $gallery->file = Storage::url($gallery->file);
        }

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_GALLERY, $galleries);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:Foto,Video',
            'file' => 'required|file|mimes:jpeg,png|max:5120',
            'category_id' => 'required|exists:category_galleries,id'
        ]);
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();

            $category = CategoryGallery::find($input['category_id']);
            if (!$category) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Categori'), 400);
            }

            $file = $request->file('file');
            $filePath = $file->store(FileConstant::FOLDER_GALLERIES, FileConstant::FOLDER_PUBLIC);

            if (!$file->isValid()) {
                return ApiResponse::error('File is not valid', 400);
            }
            $fileContent = file_get_contents($file->getRealPath());
            if (!$fileContent) {
                return ApiResponse::error('Error reading the file content', 500);
            }

            $fileName = basename($filePath);

            $gallery = Gallery::create([
                'title' => $input['title'],
                'type' => $input['type'],
                'file' => $filePath,
                'file_name' => $fileName,
                'category_id' => $category->id,
            ]);

            if (!$gallery) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'gallery'), 404);
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
        $category = CategoryGallery::find($gallery->category_id);
        if ($category) {
            $gallery->category_name = $category->name;
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
            $filePath = $gallery->file;
            if (!Storage::disk(FileConstant::FOLDER_PUBLIC)->exists($filePath)) {
                return ApiResponse::error('File not found', 404);
            }

            $fileContent = Storage::disk(FileConstant::FOLDER_PUBLIC)->get($filePath);

            $mimeType = File::mimeType(Storage::disk(FileConstant::FOLDER_PUBLIC)->path($filePath));

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
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|in:Foto,Video',
            'file' => 'nullable|file|mimes:jpeg,png|max:5120',
            'category_id' => 'nullable|exists:category_galleries,id'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $gallery = Gallery::find($id);

        if (!$gallery) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Gallery'), 404);
        }

        try {
            $input = $request->only(['title', 'type', 'file', 'category_id', 'file_name']);

            if (isset($input['category_id']) && $input['category_id'] !== null) {
                $category = CategoryGallery::find($input['category_id']);
                if (!$category) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Category'), 400);
                }
            } else {
                unset($input['category_id']);
            }

            if ($request->hasFile('file')) {
                Storage::disk(FileConstant::FOLDER_PUBLIC)->delete($gallery->file);

                $file = $request->file('file');
                $filePath = $file->store(FileConstant::FOLDER_GALLERIES, FileConstant::FOLDER_PUBLIC);
                $input['file'] = $filePath;

                $fileName = basename($filePath);
                $input['file_name'] = $fileName;
            }

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
