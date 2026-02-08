<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SummernoteController extends Controller
{
    /**
     * Summernote 단일 이미지 업로드 처리
     *
     * @param Request $request
     * @return json{url, alt}
     * 
     * - 유효성 검증 : image + mime + 용량제한 
     * - 저장 디스크 : pubilc
     * 
     */
    public function uploadImage(Request $request){

    }
}
