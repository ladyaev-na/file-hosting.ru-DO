<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileStoreRequest;
use App\Models\Right;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class FileController extends Controller
{
    public function store(FileStoreRequest $request)
    {

        $path = 'uploads/' . $request->user()->id . '/';
        $user = auth()->user();
        $files = $request->file('files');


        foreach ($files as $file) {

            $fileId = random_int(0, 10);

            $Name = $file->getClientOriginalName();
            $extension = $file->extension();
            // Итоговое имя файла с расширением
            $extensionName = $Name . ".$extension";

            $file->move($path, $extensionName);

            // Сохранил в бд
            File::create([
                "name" => $Name,
                "extension" => $extension,
                "path" => $path,
                "file_id" => $fileId,
                "user_id" => $user->id
            ]);
        }
        return response()->json('файл загружен')->setStatusCode(200,'спешно');
    }

    /*Не понял как делать*/
    /* public function edit(Request $request, $file_id) {


          $file = File::where('file_id', $file_id)->first();

          // Возвращаем успешный ответ
          return response()->json([
              'success' => true,
              'code' => 200,
              'message' => 'Renamed'
          ]);
      }*/


    public function destroy($id) {
        // Ищем файл
        $file = File::where("file_id", "=", $id)->first();
        $user = auth()->user();

        // Если запись о файле в базе найдена
        if ($file) {
            // Собираем путь до файла
            $path = $file->path;
            $path .= $file->name . '.';
            $path .= $file->extension;
        }

        // Удаляел файл с диска
        unlink($path);
        // Удаляел файле из базы
        $file->delete();

        return response()->json([
            "success" => true,
            "code" => 200,
            "message" => "File deleted"
        ]);
    }

    public function download($id) {
        $file = File::where("file_id", "=", $id)->first();
        $user = auth()->user();

        // Если запись о файле в базе найдена
        if ($file) {
            // Собираем путь до файла
            $path = $file->path;
            $path .= $file->name . '.';
            $path .= $file->extension;
        }

        $right = Right::where("file_id", "=", $file->id)
            ->where("user_id", "=", $user->id)->first();
       // если нет прав нельзя!
        if ($user->id != $file->user_id && !$right) {
            return response()->json([
                "message" => "Forbidden for you"
            ])->setStatusCode(403);
        }

        // Скачиваю
        return response()->download($path, basename($path));
    }


    public function owned(Request $request) {

        $currUser = auth()->user();
        $files = File::where("user_id", "=", $currUser->id)->get();
        $response = [];

        foreach ($files as $file) {
            $accesses = [];
            foreach ($file->rights as $right) {
                $accesses[] = [
                    'fullname' => $right->user->full_name,
                    'email' => $right->user->email,
                    'type' => 'co-author',
                ];
            }

            $response[] = [
                'file_id' => $file->file_id,
                'name' => $file->name,
                'code' => 200,
                'url' => url("files/{$file->file_id}"),
                'accesses' => $accesses
            ];
        }

        return response()->json($response);
    }

    public function allowed(Request $request) {

        $currUser = auth()->user();
        // Доступные файлы
        $rights = Right::where("user_id", "=", $currUser->id)->get();
        $response = [];

        foreach ($rights as $right) {
            $files = File::where("id", "=", $right->file_id)->get();
            foreach ($files as $file) {

                $response[] = [
                    "file_id" => $file->file_id,
                    "name" => $file->name,
                    "code" => 200,
                    "url" => url("files/{$file->file_id}")
                ];
            }
        }

        return response()->json($response, 200);
    }
}
