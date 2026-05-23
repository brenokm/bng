<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;
use bng\System\SendEmail;

class Main extends BaseController
{

    public function index()
    {

        if (!check_session()) {
            $this->login_frm();
            return;
        }
        logger($_SESSION['user']->name . '- fez login', 'info');
        $data['user'] = $_SESSION['user'];
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('homepage', $data);
        $this->view('layouts/html_footer');
    }

    // login
    public function login_frm()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        $data = [];
        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header');
        $this->view('login_frm', $data);
        $this->view('layouts/html_footer');
    }

    public function login_submit()
    {
        if (check_session()) {
            $this->index();
            return;
        }
        if ($_SERVER["REQUEST_METHOD"] != 'POST') {
            $this->index();
            return;
        }
        // aviso de campos obrigatórios caso VAZIOS
        $validation_errors = [];
        if (empty($_POST['text_username']) && empty($_POST['text_password'])) {
            $validation_errors[] = "os campos são obrigatórios";
        }
        $username = $_POST['text_username'];
        $password = $_POST['text_password'];
        //VALIDAÇÃO DE EMAIL
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = 'Insira o e-mail válido';
        }
        if (strlen($username) < 5 || strlen($username) > 50) {
            $validation_errors[] = 'necessario que o e mail tenha entre 5 e 50 caracteres';
        }
        //VALIDAÇÃO DE SENHA
        if (strlen($password) < 6 || strlen($password) > 12) {
            $validation_errors[] = 'necessario que a senha tenha entre 6 e 12 caracteres';
        }

        //
        if (!empty($validation_errors)) {
            logger($username . '- Login Inválido', 'info');
            $_SESSION['validation_errors'] = $validation_errors;
            $this->login_frm();
            return;
        }


        $model = new Agents();
        $result = $model->check_login($username, $password);

        if (!$result['status']) {
            logger("$username - login inválido", 'error');
            $_SESSION['server_error'] = 'login invalido';
            $this->login_frm();
            return;
        }
        $results = $model->get_user_data($username);
        $_SESSION['user'] = $results['data'];
        $results = $model->set_user_last_login($_SESSION['user']->id);
        $this->index();
    }
    public function logout()
    {
        if (!check_session()) {
            $this->index();
            return;
        }

        logger($_SESSION['user']->name . '- fez logout', 'info');
        unset($_SESSION['user']);
        $this->index();
    }

    public function change_password_frm()
    {

        if (!check_session()) {
            $this->index();
            return;
        }
        $data['user'] = $_SESSION['user'];
        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('profile_change_password_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function change_password_submit()
    {
        if (!check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER["REQUEST_METHOD"] != 'POST') {
            $this->index();
            return;
        }

        $validation_errors = [];
        $text_current_password = $_POST['text_current_password'];
        $text_new_password = $_POST['text_new_password'];
        $text_repeat_new_password = $_POST['text_repeat_new_password'];
        if (empty($text_current_password)) {
            $validation_errors[] = 'A senha atual é obrigatória';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }
        if (empty($text_new_password)) {
            $validation_errors[] = 'A nova senha  é obrigatória';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }

        if (empty($text_repeat_new_password)) {
            $validation_errors[] = 'A repetição de senha  é obrigatória';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }

        if (strlen($text_current_password) < 6 || strlen($text_current_password) > 12) {
            $validation_errors[] = 'A senha deve ter entre 6 e 12 caracteres';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }
        if (strlen($text_new_password) < 6 || strlen($text_new_password) > 12) {
            $validation_errors[] = 'A nova senha deve ter entre 6 e 12 caracteres';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }
        if (strlen($text_repeat_new_password) < 6 || strlen($text_repeat_new_password) > 12) {
            $validation_errors[] = 'A nova senha deve ter entre 6 e 12 caracteres';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }

        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $text_current_password)) {
            $validation_errors[] = '';
            $_SESSION['validation_errors'] = $validation_errors;
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $text_new_password)) {
            $validation_errors[] = '';
            $_SESSION['validation_errors'] = $validation_errors;
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $text_repeat_new_password)) {
            $validation_errors[] = '';
            $_SESSION['validation_errors'] = $validation_errors;
            return;
        }

        if ($text_new_password != $text_repeat_new_password) {
            $validation_errors[] = 'As senhas devem ser identicas';
            $_SESSION['validation_errors'] = $validation_errors;
            $this->change_password_frm();
            return;
        }

        $model = new Agents;
        $result =  $model->check_current_password($text_current_password);

        if (!$result) {
            $server_error[] = 'A senha atual não está correta';
            $_SESSION['server_error'] = $server_error;
            $this->change_password_frm();
            return;
        }
        $model->update_agent_password($text_new_password);

        $username = $_SESSION['user']->name;
        logger("$username - password alterado com sucesso no perfil de utilizador");

        $data['user'] = $_SESSION['user'];
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('profile_change_password_success', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function define_password($purl = '')
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if (empty($purl) || strlen($purl) != 20) {
            die('erro nas credenciais');
        }
        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }


        $model = new Agents();
        $results = $model->check_new_agent_purl($purl);
        // printData($purl);  purl ok
        if (!$results['status']) {
            die('erro nas credenciais: purl inexistente ');
        }

        $data['purl'] = $purl;
        $data['id'] = $results['id'];

        $this->view('layouts/html_header');
        $this->view('new_agent_define_password', $data);
        $this->view('layouts/html_footer');
    }
    public function define_password_submit()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }
        if (empty($_POST['purl']) || empty($_POST['id']) || strlen($_POST['purl']) != 20) {
            $this->index();
            return;
        }

        $id = aes_decrypt($_POST['id']);
        $purl = $_POST['purl'];

        if (!$id) {
            $this->index();
            return;
        }

        if (empty($_POST['text_password'])) {
            $_SESSION['validation_errors'] = 'Password é de preenchimento obrigatório';
            $this->define_password($purl);
            return;
        }

        if (empty($_POST['text_repeat_new_password'])) {
            $_SESSION['validation_errors'] = 'repitir a Password é de preenchimento obrigatório';
            $this->define_password($purl);
            return;
        }

        $password = $_POST['text_password'];
        $repeat_password = $_POST['text_repeat_new_password'];


        // use positive look ahead
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $password)) {
            $_SESSION['validation_errors'] = "A password deve ter, pelo menos, uma maiúscula, uma minúscula e um número.";
            $this->define_password($purl);
            return;
        }

        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeat_password)) {
            $_SESSION['validation_errors'] = "A repetição da password deve ter, pelo menos, uma maiúscula, uma minúscula e um número.";
            $this->define_password($purl);
            return;
        }

        // check if the password and repeat password are equal values
        if ($password != $repeat_password) {
            $_SESSION['validation_errors'] = "A password e a sua repetição não são iguais.";
            $this->define_password($purl);
            return;
        }

        // updates the database with the agent's password
        $model = new Agents();
        $model->set_agent_password($id, $password);

        // logger
        logger("Foi definida com sucesso a password para o agente ID = $id (purl: $purl)");

        $this->view('layouts/html_header');
        $this->view('reset_password_define_password_success');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function reset_password()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        $data = [];

        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        // display the view with success page
        $this->view('layouts/html_header');
        $this->view('reset_password_frm', $data);
        $this->view('layouts/html_footer');
    }
    public function reset_password_submit()
    {
        if (check_session()) {
            $this->index();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        if (empty($_POST['text_username'])) {
            $_SESSION['validation_error'] = "Utilizador é de preenchimento obrigatório.";
            $this->reset_password();
            return;
        }
        if (!filter_var($_POST['text_username'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['validation_error'] = "Utilizador tem que ser um email válido.";
            $this->reset_password();
            return;
        }

        $username = $_POST['text_username'];

        $model = new Agents();
        $results = $model->set_code_for_recover_password($username);

        if ($results['status'] == 'error') {

            logger("Aconteceu um erro na criação do código de recuperação da password. User: $username", 'error');

            $_SESSION['validation_error'] = "Aconteceu um erro inesperado. Por favor tente novamente.";
            $this->reset_password();
            return;
        }

        $id = $results['id'];
        $code = $results['code'];

        // code is stored. Send email with the code
        $email = new SendEmail();
        $results = $email->send_email(APP_NAME . ' Código para recuperar a password', 'codigo_recuperar_password', ['to' => $username, 'code' => $results['code']]);

        if ($results['status'] == 'error') {
            // logger
            logger("Aconteceu um erro no envio do email com o código de recuperação da password. User: $username", 'error');

            $_SESSION['validation_error'] = "Aconteceu um erro inesperado. Por favor tente novamente.";
            $this->reset_password();
            return;
        }

        // logger
        logger("Email com código de recuperação de password enviado com sucesso. User: $username | Code: $code");

        // the email was sent. Show the next view
        $this->insert_code(aes_encrypt($id));
    }

    private function insert_code($id = '')
    {

        // if there is a open session, gets out!
        if (check_session()) {
            $this->index();
            return;
        }

        // check if id is valid
        if (empty($id)) {
            $this->index();
            return;
        }

        $id = aes_decrypt($id);
        if (!$id) {
            $this->index();
            return;
        }

        $data['id'] = $id;

        // check for validation errors or server errors
        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        // display the view
        $this->view('layouts/html_header');
        $this->view('reset_password_insert_code', $data);
        $this->view('layouts/html_footer');
    }

    public function insert_code_submit($id = '')
    {
        // if there is a open session, gets out!
        if (check_session()) {
            $this->index();
            return;
        }

        // check if id is valid
        if (empty($id)) {
            $this->index();
            return;
        }

        $id = aes_decrypt($id);
        if (!$id) {
            $this->index();
            return;
        }

        // check if his a post
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        // form validation
        if (empty($_POST['text_code'])) {
            $_SESSION['validation_error'] = "Código é de preenchimento obrigatório.";
            $this->insert_code(aes_encrypt($id));
            return;
        }

        $code = $_POST['text_code'];
        if (!preg_match("/^\d{6}?$/", $code)) {
            $_SESSION['validation_error'] = "O código é constituído por 6 algarismos.";
            $this->insert_code(aes_encrypt($id));
            return;
        }

        // check if the code is the same that is stored in the database
        $model = new Agents();
        $results = $model->check_if_reset_code_is_correct($id, $code);

        if (!$results['status']) {

            $_SESSION['server_error'] = "Código incorreto.";
            $this->insert_code(aes_encrypt($id));
            return;
        }

        // the code is correct. Let's define the password
        $this->reset_define_password(aes_encrypt($id));
    }

    public function reset_define_password($id = '')
    {
        // if there is a open session, gets out!
        if (check_session()) {
            $this->index();
            return;
        }

        // check if id is valid
        if (empty($id)) {
            $this->index();
            return;
        }

        $id = aes_decrypt($id);
        if (!$id) {
            $this->index();
            return;
        }

        $data['id'] = $id;

        // check for validation error
        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        // check for server error
        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        // display the form to define de new password
        $this->view('layouts/html_header');
        $this->view('reset_password_define_password_frm', $data);
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function reset_define_password_submit($id = '')
    {
        // if there is a open session, gets out!
        if (check_session()) {
            $this->index();
            return;
        }

        // check if id is valid
        if (empty($id)) {
            $this->index();
            return;
        }

        $id = aes_decrypt($id);
        if (!$id) {
            $this->index();
            return;
        }

        // check if there was a post
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->index();
            return;
        }

        // form validation
        if (empty($_POST['text_new_password'])) {
            $_SESSION['validation_error'] = "Nova password é de preenchimento obrigatório.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }
        if (empty($_POST['text_repeat_new_password'])) {
            $_SESSION['validation_error'] = "A repetição da nova password é de preenchimento obrigatório.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }

        // get the input values
        $new_password = $_POST['text_new_password'];
        $repeat_new_password = $_POST['text_repeat_new_password'];

        // check if all passwords have more than 6 and less than 12 characters
        if (strlen($new_password) < 6 || strlen($new_password) > 12) {
            $_SESSION['validation_error'] = "A nova password deve ter entre 6 e 12 caracteres.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }
        if (strlen($repeat_new_password) < 6 || strlen($repeat_new_password) > 12) {
            $_SESSION['validation_error'] = "A repeição da nova password deve ter entre 6 e 12 caracteres.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }

        // check if all password have, at least one upper, one lower and one digit

        // use positive look ahead
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $new_password)) {
            $_SESSION['validation_error'] = "A nova password deve ter, pelo menos, uma maiúscula, uma minúscula e um dígito.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }
        if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeat_new_password)) {
            $_SESSION['validation_error'] = "A repetição da nova password deve ter, pelo menos, uma maiúscula, uma minúscula e um dígito.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }

        // check if both passwords are equal
        if ($new_password != $repeat_new_password) {
            $_SESSION['validation_error'] = "As nova password e a sua repetição devem ser iguais.";
            $this->reset_define_password(aes_encrypt($id));
            return;
        }

        // updates the agent's password in the database
        $model = new Agents();
        $model->change_agent_password($id, $new_password);

        // logger
        logger("Foi alterada com sucesso a password do user ID: $id após pedido de reset da password.");

        // display success page
        $this->view('layouts/html_header');
        $this->view('profile_change_password_success');
        $this->view('layouts/html_footer');
    }
}
