<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Rules\MatchOldPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request) {
        $rules = [
            'name' => 'required|max:255',
            'email' => 'required|email:dns|unique:m_users',
            'gender' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'password' => 'required',
            'confirm_password' => 'required|same:password'
        ];

        $customMessage = [
            'name.required' => 'Nama pengguna harus diisi!',
            'name.max' => 'Nama pengguna terlalu panjang, maksimal 255 karakter!',
            'email.required' => 'Email harus diisi!',
            'email.email' => 'Email yang anda masukkan tidak valid / salah.',
            'email.unique' => 'Email yang anda masukkan sudah digunakan.',
            'gender.required' => 'Jenis kelamin harus diisi!',
            'phone.required' => 'No Hp harus diisi!',
            'address.required' => 'Alamat harus diisi!',
            'password.required' => 'Password harus diisi!',
            'confirm_password.required' => 'Konfirmasi password harus diisi!',
            'confirm_password.same' => 'Konfirmasi password harus sama dengan password!',
        ];

        $validatedData = $request->validate($rules, $customMessage);
        $getRoleDonor = Role::firstWhere('name', 'Donatur');

        $otherData = [];

        $otherData['user_code'] = UserController::generateKode();

        if (!empty($getRoleDonor)) {
            $m_role_id = $getRoleDonor->id;
            $otherData['m_role_id'] = $m_role_id;
        } else {
            return redirect()->back()->with('error', 'Terjadi kesalahan pada server!');
        }

        $otherData['image'] = 'default.png';

        $validatedData = collect($validatedData)->except('confirm_password')->toArray();

        $data = array_merge($validatedData, $otherData);

        $registerDonor = User::create($data);

        if (!empty($registerDonor)) {
            // add role
            User::find($registerDonor->id)->assignRole($getRoleDonor->name);
            return redirect('/login')->with('registerSuccess', 'Registrasi pengguna berhasil dilakukan, Silakan login untuk masuk ke dalam aplikasi.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan pada server!');
        }
    }

    public function authenticate(Request $request) {
        $email = $request->input('email');
        $password = $request->input('password');

        $userLogin = User::where('deleted_at', null)->where('email', $email)->first();

        $rules = [
            'email' => 'required|email:dns',
            'password' => 'required'
        ];

        $customMessage = [
            'email.required' => 'Email harus diisi! masukkan email anda.',
            'email.email' => 'Email yang anda masukkan tidak valid.',
            'password.required' => 'Password harus diisi!, masukkan password anda.'
        ];

        $request->validate($rules, $customMessage);

        if ($userLogin !== null) {
            $credentials = [
                'email' => $email,
                'password' => $password
            ];

            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'))->with('loginSuccess', 'Anda berhasil login!');
            } else {
                return redirect()->back()->with('loginError', 'Pastikan anda memasukkan informasi login yang benar');
            }
        } else {
            return redirect()->back()->with('loginError', 'Pastikan anda memasukkan informasi login yang benar');
        }
    }

    public function logout(Request $request) {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if (!empty($request->action) && $request->action == 'change-password') {
            return redirect()->route('login')->with('logoutSuccess', 'Ubah password berhasil dilakukan. Silakan login untuk masuk ke dalam aplikasi');
        } else {
            return redirect()->route('login')->with('logoutSuccess', 'Anda berhasil logout!');
        }
    }

    public function saveChangePassword(Request $request) {
        $rules = [
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => 'required|min:8|different:current_password',
            'confirm_new_password' => 'required|same:new_password'
        ];

        $customMessage = [
            'current_password.required' => 'Password lama tidak boleh kosong, isi password lama terlebih dahulu!',
            'new_password.required' => 'Password baru tidak boleh kosong, isi password baru terlebih dahulu!',
            'new_password.min' => 'Pastikan password baru anda memiliki setidaknya 8 karakter.',
            'new_password.different' => 'Password baru harus berbeda dengan password saat ini!',
            'confirm_new_password.required' => 'Konfirmasi password baru tidak boleh kosong, isi konfirmasi password baru terlebih dahulu!',
            'confirm_new_password.same' => 'Konfirmasi password baru harus sama dengan password baru!'
        ];

        // Validation input
        try {
            $validatedData = $request->validate($rules, $customMessage);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session(['change_password_error' => 'change_password_error']);
            return redirect()->back()->withErrors($e->validator)->withInput();
        }

        // set action another alert logout
        $request['action'] = 'change-password';

        $user = auth()->user();

        if (!empty($user)) {
            $updatePassword = User::where('id', $user->id)->update(['password' => Hash::make($validatedData["new_password"])]);

            if (!empty($updatePassword)) {
                return app()->call('App\Http\Controllers\AuthController@logout');
            } else {
                return redirect()->back()->with('error', 'Terjadi kesalahan pada server!');
            }
        } else {
            return redirect()->back()->with('error', 'Terjadi kesalahan pada server!');
        }
    }
}
