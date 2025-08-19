<?php

namespace App\Controllers;

use App\Models\UsuariosModel;
use \App\Models\TokenModel;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Auth extends BaseController
{
    public function index()
    {
        return view('auth/login');
    }

    public function login()
    {
        $post = $this->request->getPost();

        $email = $post['email'];
        $password = $post['password'];

        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[3]',
        ];

        if (!$this->validate($rules)) {
            return view('auth/login', [
                'error' => $this->validator->getErrors(),
            ]);
        }

        $userModel = new UsuariosModel();
        $user = $userModel->where('Correo', $email)->first();

        if ($user && password_verify($password, $user['Contrasena'])) {
            $token = new Rest();
            $tokenModel = new TokenModel(); // Assuming you have a TokenModel
            $existingToken = $tokenModel->where('ID_Usuario', $user['ID_Usuario'])->first();
            if ($existingToken) {
                $token->updateToken($user['ID_Usuario'], $token->generatetoken($user['ID_Usuario']));
            } else {
                $token->generateUserToken($user['ID_Usuario']);
            }
            $ses_data = [
                'id' => $user['ID_Usuario'],
                'name' => $user['Nombre'],
                'email' => $user['Correo'],
                'isLoggedIn' => true,
            ];
            $this->session->set($ses_data);

            return redirect()->to('/');
        }

        return view('auth/login', ['error' => 'Correo electrónico o contraseña no válidos.']);
    }
    public function logout()
    {
        $token = new Rest();
        $userModel = new UsuariosModel();
        $user = $userModel->find($this->session->get('id'));
        if ($user) {
            $token->updateToken($user['ID_Usuario'], null);
        }
        $this->session->destroy();
        return redirect()->to('/auth');
    }
}
