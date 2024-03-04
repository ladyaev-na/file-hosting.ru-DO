<?php

namespace App\Http\Controllers;

use App\Models\Right;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\File;
use App\Models\User;

class RightController extends Controller
{
    public function add(Request $request, $id) {
        // Првоеряем поля через валидацию
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|min:1|max:255',
        ]);

        // Если есть ошибки, то выводим
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Текущий пользователь
        $currUser = auth()->user();
        // Ищем файл
        $file = File::where("file_id", "=", $id)->first();

        $path = "";

        // Если запись о файле в базе найдена
        if ($file) {
            // Собираем путь до файла
            $path = $file->path;
            $path .= $file->name . '.';
            $path .= $file->extension;
        }

        // Если файла нет в базе или на диске,
        // то выкидываем ошибку
        if (!$file || !file_exists($path)) {
            return response()->json([
                "message" => "Not found",
                "code"=> 404
            ])->setStatusCode(404);
        }

        // Проверяем права доступа к файлу
        if ($currUser->id != $file->user_id) {
            return response()->json([
                "message" => "Forbidden for you"
            ])->setStatusCode(403);
        }

        $newUSer = User::where("email", "=", $request->email)->first();

        // Если пользователя нет
        if (!$newUSer) {
            return response()->json([
                "message" => "User not found",
                "code"=> 404
            ])->setStatusCode(404);
        }

        $right = Right::where("user_id", "=", $newUSer->id)
            ->where("file_id", "=", $file->id)->first();
        
        if (!$right && $newUSer->id != $currUser->id) {
            Right::create([
                "user_id" => $newUSer->id,
                "file_id" => $file->id
            ]);
        }

        $allRights = Right::where("file_id", "=", $file->id)->get();

        $response = [
            [
                "firstName" => $currUser->first_name,
                "email" => $currUser->email,
                "type" => "author",
                "success" => 200
            ]
        ];

        foreach ($allRights as $right) {
            $user = User::find($right->user_id);
            array_push($response, [
                "firstName" => $user->first_name,
                "email" => $user->email,
                "type" => "co-author",
                "success" => 200
            ]);
        }

        return response()->json($response);
    }

    public function destroy(Request $request, $id) {
        // Првоеряем поля через валидацию
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|min:1|max:255',
        ]);

        // Если есть ошибки, то выводим
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        // Текущий пользователь
        $currUser = auth()->user();
        // Ищем файл
        $file = File::where("file_id", "=", $id)->first();

        $path = "";

        // Если запись о файле в базе найдена
        if ($file) {
            // Собираем путь до файла
            $path = $file->path;
            $path .= $file->name . '.';
            $path .= $file->extension;
        }

        // Если файла нет в базе или на диске,
        // то выкидываем ошибку
        if (!$file || !file_exists($path)) {
            return response()->json([
                "message" => "Not found",
                "code"=> 404
            ])->setStatusCode(404);
        }

        // Проверяем права доступа к файлу
        if ($currUser->id != $file->user_id) {
            return response()->json([
                "message" => "Forbidden for you"
            ])->setStatusCode(403);
        }

        $newUSer = User::where("email", "=", $request->email)->first();

        // Если пользователя нет
        if (!$newUSer) {
            return response()->json([
                "message" => "User not found",
                "code"=> 404
            ])->setStatusCode(404);
        }

        $right = Right::where("user_id", "=", $newUSer->id)
            ->where("file_id", "=", $file->id)->first();
        
        // Если пользователь, у которого забираем права, имел права доступа
        if ($right) {
            // То забираем у него права
            $right->delete();
        } else {
            // Иначе выводим ошибку
            return response()->json([
                "message"=> "The user no longer has rights",
                "code" => 404
            ])->setStatusCode(404);
        }

        $allRights = Right::where("file_id", "=", $file->id)->get();

        $response = [
            [
                "firstName" => $currUser->first_name,
                "email" => $currUser->email,
                "type" => "author",
                "success" => 200
            ]
        ];

        foreach ($allRights as $right) {
            $user = User::find($right->user_id);
            array_push($response, [
                "firstName" => $user->first_name,
                "email" => $user->email,
                "type" => "co-author",
                "success" => 200
            ]);
        }

        return response()->json($response);
    }
}
