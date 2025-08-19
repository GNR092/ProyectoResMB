<?php
namespace App\Libraries;
use App\Models\TokenModel;
use App\Libraries\HttpStatus;
use App\Models\UsuariosModel;

class Rest
{
    /**
     * Genera un nuevo token API.
     *
     * @return bool True si el token se generó correctamente, false en caso contrario.
     * @param int $userid El ID del usuario para el cual se generará el token
     */
    public function generateUserToken(int $userid): bool
    {
        $tokenmodel = new TokenModel();
        $tokenhash = $this->generatetoken($userid);

        if (!$tokenhash) {
            return false;
        }

        $usertoken = [
            'ID_Usuario' => $userid,
            'token' => $tokenhash,
        ];

        return $tokenmodel->insert($usertoken) !== false;
    }

    public function generatetoken(int $userid): string
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user) {
            return null;
        }

        $tokenmodel = new TokenModel();

        $tokenmodel->where('ID_Usuario', $userid)->delete();

        do {
            $tokenhash = bin2hex(random_bytes(32));
        } while ($tokenmodel->where('token', $tokenhash)->first());

        return $tokenhash;
    }
    
    public function updateToken(int $userid, ?string $token): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userid)->first();

        if (!$tokenData) {
            return false;
        }
 
        $dataToUpdate = [
            'token'      => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $tokenModel->update($tokenData['ID_Token'], $dataToUpdate);
    }
    /**
     * Borra un token API.
     *
     * @return bool true su el token se eliminó correctamente, false en caso contrario.
     * @param int $userId El ID del usuario cuyo token se eliminará
     */
    public function deleteToken(int $userId): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userId)->first();

        if (!$tokenData) {
            return false;
        }

        if ($tokenModel->delete($tokenData['ID_Token'])) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Obtiene todos los tokens de la base de datos.
     * @return array Los tokens encontrados
     */
    private function getTokens(): array
    {
        $tokenModel = new TokenModel();
        $tokens = $tokenModel->findall();
        return $tokens;
    }
}
