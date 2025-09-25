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
        $loginAsEmployee = isset($post['login_as_employee']) && $post['login_as_employee'] == '1';

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

        if (!$user) {
            return view('auth/login', ['error' => 'Correo electrónico o contraseña no válidos.']);
        }

        // Determinar qué contraseña verificar
        $passwordToVerify = $loginAsEmployee ? $user['ContrasenaG'] : $user['ContrasenaP'];
        $isPasswordCorrect = password_verify($password, $passwordToVerify);

        if ($isPasswordCorrect) {
            $token = new Rest();
            $tokenModel = new TokenModel();
            $existingToken = $tokenModel->where('ID_Usuario', $user['ID_Usuario'])->first();
            if ($existingToken) {
                $token->updateToken($user['ID_Usuario'], $token->generatetoken($user['ID_Usuario']));
            } else {
                $token->generateUserToken($user['ID_Usuario']);
            }

            // Obtener datos completos para la sesión
            $api = new Rest();
            $userData = $api->getUserById($user['ID_Usuario']);
            $departmentData = $api->getAllDepartments();
            $userDepartment = null;
            foreach ($departmentData as $dept) {
                if ($dept['ID_Dpto'] == $userData['ID_Dpto']) {
                    $userDepartment = $dept;
                    break;
                }
            }

            $ses_data = [
                'id' => $userData['ID_Usuario'],
                'nombre_usuario' => $userData['Nombre'],
                'email' => $userData['Correo'],
                'id_departamento_usuario' => $userData['ID_Dpto'],
                'departamento_usuario' => $userDepartment ? ($userDepartment['Nombre'] . ' (' . $userDepartment['Place'] . ')') : 'N/A',
                'isLoggedIn' => true,
                'login_type' => $loginAsEmployee ? 'employee' : 'boss', // Guardamos el tipo de login
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
