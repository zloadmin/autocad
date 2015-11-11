<?php

namespace App\Http\Controllers;

use App\Files;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;

class FilesController extends Controller
{

    public function create()
    {
        return View('files.create');
    }


    public function store(Request $request)
    {

        // 1. Проходим валидацию

        $rules = [
            'name' => 'required',
            'type' => 'required|in:1,2',
            'file' => 'required'
        ];
        $messages = [
            'name.required' => 'Поле "Название файла" обязательно к заполнению',
            'type.required' => 'Срок действия обязательно к заполнению',
            'type.in' => 'Неправильно выбран срок действия',
            'date.required' => 'Не выбрана дата оканчания',
            'date.date_format' => 'Не правильный формат даты окончания',
            'date.after' => 'Выбрана уже наступившаяя дата',
            'period.required' => 'Не выбран период',
            'period.integer' => 'Ошибка выбора периода',
            'file.required' => 'Не выбран файл',
        ];



        //Если выбрана дата
        if($request->input('type')=="1") {
            $rules = array_add($rules, 'date', 'required|date_format:Y-m-d H:i:s|after:now');
        //Если выбран период
        } elseif($request->input('type')=="2") {
            $rules = array_add($rules, 'period', 'required|integer');
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        // 2. Сохраняем файл

        $name = md5(time().str_random(10));

        $directory = base_path()."/files/gdl/";

        $request->file('file')->move($directory, $name.".gsm"); //Exception

        // 3. Конвертируем в xml

        $nix_gdl_file = base_path().'/files/gdl/'.$name.".gsm";

        $nix_xml_file = base_path().'/files/xml/'.$name.".xml";


        $win_gdl_file = "Z:".str_replace("/", "\\", $nix_gdl_file);

        $win_xml_file = "Z:".str_replace("/", "\\", $nix_xml_file);

        $comand = 'wine '.base_path().'/autocad/LP_XMLConverter.exe libpart2xml -l UTF8 "'.$win_gdl_file.'" "'.$win_xml_file.'"';

        exec($comand);

        // 4. Проверяем наличе нужных переменных
        /*
        if(!is_file($nix_xml_file)) return redirect()->back()->withErrors('Ошибка конвертирование в xml формат');
        */
        // 5. Добовлям в базу


        $newfile = new Files();

        $newfile['name'] = $request->input('name');
        $newfile['md5_name'] = $name;
        $newfile['original_name'] = $_FILES['file']['name'];

        if($request->input('type')==1) {
            $newfile['last_date'] = $request->input('date');
            $newfile['type'] = 1;
        } else {
            $newfile['reload'] = $request->input('period');
            $newfile['type'] = 2;
        }

        $newfile->save();

        // 6. Редирект на лист файов

        return redirect()->to('list');

    }


    public function files_list()
    {
        $all_files = Files::all();

        return View('files.list')->with('files', $all_files);
    }


    public function destroy($id)
    {
        //
    }
}
