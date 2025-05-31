<?php

use Illuminate\Support\Facades\Route;
use App\Exports\TemplateExport;
use Maatwebsite\Excel\Facades\Excel;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/download-template', function() {
    return Excel::download(new TemplateExport, 'template.xlsx');
})->name('download-template');