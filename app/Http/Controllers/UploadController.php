<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;

class UploadController extends Controller
{
    public function upload(Request $request){
        $file = $request->file('file');
        $extension = $file->extension();
        $allowed_extensions = config('media.extension_whitelist');
        $errors = new MessageBag();
        if(!in_array($extension, $allowed_extensions)){
            $errors->add("illegal_filetype", "This file type is not supported, must be one of the following: " . join(', ' , $allowed_extensions));
        }
    }
}
