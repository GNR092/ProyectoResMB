<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class Auth extends BaseController
{
    public function index()
    {
        return view("auth/login");
    }

    public function login()
    {
        $post = $this->request->getPost();

        $email = $post["email"];
        $password = $post["password"];

        $rules = [
            "email" => "required|valid_email",
            "password" => "required|min_length[3]",
        ];

        if (!$this->validate($rules)) {
            return view("auth/login", [
                "error" => $this->validator->getErrors(),
            ]);
        }

        $userModel = new UsuarioModel();
        $user = $userModel->where("Correo", $email)->first();

        if ($user && password_verify($password, $user["Contrasena"])) {
            $ses_data = [
                "id" => $user["ID_Usuario"],
                "name" => $user["Nombre"],
                "email" => $user["Correo"],
                "isLoggedIn" => true,
            ];
            $this->session->set($ses_data);

            return redirect()->to("/");
        }

        return view("auth/login", ["error" => "Invalid email or password."]);
    }
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to("/auth");
    }
}
