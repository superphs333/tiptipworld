<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * 일반 회원가입 컨트롤러 
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * 일반 회원가입 요청 처리
     * 
     * [역할]
     * - 사용자가 입력한 이름/이메일/비밀번호를 검증
     * - 새 User 레코드를 생성
     * - 이 사용자는 로컬 비밀번호 로그인 가능 상태를 password_set_at 으로 기록
     * - 회원가입 이벤트를 발생
     * - 방금 만든 사용자로 즉시 로그인 처리
     * - 가입 후 화면으로 이동 
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'password_set_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('home', absolute: false));
    }
}
