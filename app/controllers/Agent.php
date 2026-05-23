<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;



class Agent extends BaseController
{
    // ======================================================= // ======================================================= //
    public function my_clients()
    {

        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
            exit;
        }

        $id_agent = $_SESSION['user']->id;
        $model = new Agents();
        $results = $model->get_agent_clients($id_agent);
        // printData($results);


        $data['user'] = $_SESSION['user'];
        $data['clients'] = $results['data'];

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agent_clients', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    // ======================================================= // ======================================================= //
    public function edit_client($id)
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
        }
        $id_client = aes_decrypt($id);
        if (!$id_client) {
            header('Location:index.php');
        }
        $model = new Agents();
        $results = $model->get_client_data($id_client);

        if ($results['status'] == 'error') {
            header('Location:index.php');
        }

        $data['client'] = $results['data'];
        $data['client']->birthdate = date('d-m-Y', strtotime($data['client']->birthdate));
        $data['user'] = $_SESSION['user'];
        $data['flatpickr'] = true;

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }
        if (!empty($_SESSION['server_error'])) {
            $data['server_error']  = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('edit_client_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    // ======================================================= // ======================================================= //
    public function edit_client_submit()
    {

        if (!check_session() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:index.php');
            exit;
        }

        $validation_errors = [];

        // NOME ================
        if (empty($_POST['text_name'])) {
            $validation_errors[] = "Nome é OBRIGATÓRIO";
        } elseif (strlen($_POST['text_name']) < 3) {
            $validation_errors[] = "Nome é deve ter MAIS DE 3 LETRAS";
        }
        // DATA ================
        if (empty($_POST['text_birthdate'])) {
            $validation_errors[] = "Data de nascimento é OBRIGATÓRIO";
        } else {
            $birthdate = \DateTime::createFromFormat('d-m-Y', $_POST['text_birthdate']);

            if (!$birthdate)
                $validation_errors[] = "Data de nascimento não está no formato correto";
            else {
                $today = new \DateTime('today');
                $birthdate->setTime(0, 0, 0);
                $today->setTime(0, 0, 0);
                if ($birthdate > $today) {
                    $validation_errors[] = "Data de nascimento não pode ser futura";
                }
            }
        }
        // RADIO ================

        if (!empty($_POST['text_radio'])) {
            $validation_errors[] = "Genero é OBRIGATÓRIO";
        }
        // EMAIL ================
        if (empty($_POST['text_email'])) {
            $validation_errors[] = "Email é OBRIGATÓRIO";
        } elseif (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Email deve ter FORMATO VALIDO";
        }

        // TELEFONE ================
        if (empty($_POST['text_phone'])) {
            $validation_errors[] = "Phone é OBRIGATÓRIO";
        } else {
            if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])) {
                $validation_errors[] = "O telefone deve começar com 9 e ter 9 algarismos no total";
            }
        }
        // INTERESSES ================
        if (empty($_POST['text_interests'])) {
            $validation_errors[] = "Interesse é OBRIGATÓRIO";
        }



        // ENVIO ================
        if (empty($_POST['id_client'])) {

            header('Location:index.php');
        }

        $id_client = aes_decrypt($_POST['id_client']);
        if (!empty($validation_errors)) {
            $_SESSION['validation_errors'] = $validation_errors;
            $this->edit_client(aes_encrypt($id_client));
            return;
        }

        $model = new agents();
        $results = $model->check_other_client_with_same_name($id_client, $_POST['text_name']);

        if ($results['status']) {
            $_SESSION['server_error'] = 'já existe um cliente com esse nome';
            $this->edit_client(aes_encrypt($id_client));
            return;
        }

        $model->update_client_data($id_client, $_POST);
        logger(get_user_active() . ' - atualizou os dados do cliente ID: ' . $id_client);
        $this->my_clients();
    }
    // ======================================================= // ======================================================= //
    public function delete_client($id)
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
        }

        $id_client = aes_decrypt($id);
        if (!$id_client) {
            header('Location:index.php');
        }

        $model = new Agents();
        $results = $model->get_client_data($id_client);

        if (empty($results['data'])) {
            header('Location:index.php');
        }

        $data['user'] = $_SESSION['user'];
        $data['client'] = $results['data'];


        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('delete_client_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    // ======================================================= // ======================================================= //
    public function delete_client_confirm($id)
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location: index.php');
        }
        // check if the $id is valid
        $id_client = aes_decrypt($id);
        if (!$id_client) {
            // id_client is invalid
            header('Location: index.php');
        }

        // loads the model to delete the client's data
        $model = new Agents();
        $model->delete_client($id_client);

        // logger
        logger(get_user_active(). ' - Eliminado o cliente id: ' . $id_client);

        // returns to the agent's main page
        $this->my_clients();
    }
    // ======================================================= // ======================================================= //
    public function new_client_frm()
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
            exit;
        }
        $data['user'] = $_SESSION['user'];

        $data['flatpickr'] = true;

        if (!empty($_SESSION['validation_errors'])) {
            $data['validation_errors'] = $_SESSION['validation_errors'];
            unset($_SESSION['validation_errors']);
        }
        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('insert_client_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    // ======================================================= // ======================================================= //
    public function new_client_submit()
    {

        if (!check_session() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:index.php');
            exit;
        }

        $validation_errors = [];

        // NOME ================
        if (empty($_POST['text_name'])) {
            $validation_errors[] = "Nome é OBRIGATÓRIO";
        } elseif (strlen($_POST['text_name']) < 3) {
            $validation_errors[] = "Nome é deve ter MAIS DE 3 LETRAS";
        }
        // DATA ================
        if (empty($_POST['text_birthdate'])) {
            $validation_errors[] = "Data de nascimento é OBRIGATÓRIO";
        } else {
            $birthdate = \DateTime::createFromFormat('d-m-Y', $_POST['text_birthdate']);

            if (!$birthdate)
                $validation_errors[] = "Data de nascimento não está no formato correto";
            else {
                $today = new \DateTime('today');
                $birthdate->setTime(0, 0, 0);
                $today->setTime(0, 0, 0);
                if ($birthdate > $today) {
                    $validation_errors[] = "Data de nascimento não pode ser futura";
                }
            }
        }
        // RADIO ================

        if (!empty($_POST['text_radio'])) {
            $validation_errors[] = "Genero é OBRIGATÓRIO";
        }
        // EMAIL ================
        if (empty($_POST['text_email'])) {
            $validation_errors[] = "Email é OBRIGATÓRIO";
        } elseif (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Email deve ter FORMATO VALIDO";
        }

        // TELEFONE ================
        if (empty($_POST['text_phone'])) {
            $validation_errors[] = "Phone é OBRIGATÓRIO";
        } else {
            if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])) {
                $validation_errors[] = "O telefone deve começar com 9 e ter 9 algarismos no total";
            }
        }
        // INTERESSES ================
        if (empty($_POST['text_interests'])) {
            $validation_errors[] = "Interesse é OBRIGATÓRIO";
        }



        // ENVIO ================
        if (!empty($validation_errors)) {

            $_SESSION['validation_errors'] = $validation_errors;
            $this->new_client_frm();
            return;
        }
        $model = new Agents();
        $results = $model->check_if_client_exist($_POST);
        if ($results['status']) {
            $_SESSION['server_error'] = 'já existe um usuario com esse nome';
            $this->new_client_frm();
            return;
        }

        $model->add_new_client($_POST);
        logger(get_user_active() . '- adicionou o(a) novo(a) cliente: ' . $_POST['text_name'] . ' | ' . $_POST['text_email']);
        $this->my_clients();
    }
    // ======================================================= // ======================================================= //
    public function upload_file_form()
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
            exit;
        }

        $data['user'] = $_SESSION['user'];

        if (!empty($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }
        if (!empty($_SESSION['report'])) {
            $data['report'] = $_SESSION['report'];
            unset($_SESSION['report']);
        }

        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('upload_file_with_clients_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function upload_file_submit()
    {
        if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location:index.php');
            exit;
        }

        if (empty($_FILES) || empty($_FILES['clients_file']['name'])) {
            $_SESSION['server_error'] = 'envie um arquivo XLSX, TXT ou CSV.';
            $this->upload_file_form();
            return;
        }


        $valid_extensions = ['xlsx', 'csv', 'txt'];
        $tmp = explode('.', $_FILES['clients_file']['name']);
        $extension = strtolower(end($tmp));
        if (!in_array($extension, $valid_extensions)) {
            logger(get_user_active() . ' - tentou carregar um arquivo inválido - tipo inválido ' . $_FILES['clients_file']['name'] . "error");
            $_SESSION['server_error'] = 'o arquivo deve ser XLSX, TXT ou CSV.';
            $this->upload_file_form();
            return;
        }


        if ($_FILES['clients_file']['size'] > 20000) {
            logger(get_user_active() . ' - tentou carregar um arquivo inválido - tamanho maximo excedido ' . $_FILES['clients_file']['name'] . "error");
            $_SESSION['server_error'] = 'o arquivio deve ter no maximo 2MB';
            $this->upload_file_form();
            return;
        }

        $file_path = __DIR__ . '/../../uploads/dados_' . time() . '.' . $extension;
        if (move_uploaded_file($_FILES['clients_file']['tmp_name'], $file_path)) {


            $results = $this->has_valid_header($file_path);
            if ($results) {
                $results = $this->load_file_data_to_database($file_path);
            } else {
                logger(get_user_active() . ' - tentou carregar um arquivo inválido - header incorreto. ' . $_FILES['clients_file']['name'] .   "error");
                $_SESSION['server_error'] = 'o arquivio nao tem header no formato correto.';
                $this->upload_file_form();
                return;
            }
        } else {
            logger(get_user_active() . ' - erro inesperado no carregamento do arquivo ' . $_FILES['clients_file']['name'] .   "error");
            $_SESSION['server_error'] = 'erro inesperado no carregamento do arquivo';
            $this->upload_file_form();
            return;
        }
    }

    private function has_valid_header($file_path)
    {
        // validates the file
        $data = [];

        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv') {

            // opens the CSV file to read the header only
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(';');
            $reader->setEnclosure('');

            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray()[0];
        } else if ($file_info['extension'] == 'xlsx') {

            // opens the XLSX file to read the header only
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file_path);
            $data = $spreadsheet->getActiveSheet()->toArray()[0];
        }

        // check if the header content is valid
        $valid_header = 'name,gender,birthdate,email,phone,interests';

        return implode(',', $data) == $valid_header ? true : false;
    }

    private function load_file_data_to_database($file_path)
    {
        $data = [];

        $file_info = pathinfo($file_path);

        if ($file_info['extension'] == 'csv') {

            // opens the CSV file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(';');
            $reader->setEnclosure('');

            $sheet = $reader->load($file_path);
            $data = $sheet->getActiveSheet()->toArray();
        } else if ($file_info['extension'] == 'xlsx') {

            // opens the XLSX file
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($file_path);
            $data = $spreadsheet->getActiveSheet()->toArray();
        }

        // insert data into database
        $model = new Agents();
        $report = [
            'total' => 0,
            'total_carregados' => 0,
            'total_nao_carregados' => 0
        ];
        // extract the header from $data
        array_shift($data);

        // creates a circle to insert each record
        foreach ($data as $client) {
            if(empty($client[0]))continue;
            $report['total']++;
            // check if the client already exists in the database
            $exists = $model->check_if_client_exist([

                'text_name' => $client[0]
            ]);

            if (!$exists['status']) {

                // add client to database
                $post_data = [
                    'text_name' => $client[0],
                    'radio_gender' => $client[1],
                    'text_birthdate' => $client[2],
                    'text_email' => $client[3],
                    'text_phone' => $client[4],
                    'text_interests' => $client[5],
                ];

                $model->add_new_client($post_data);
                $report['total_carregados']++;
            } else {
                $report['total_nao_carregados']++;
            }
        }

        logger(get_user_active() . ' - carregamento do arquivo efetuado ' . $_FILES['clients_file']['name']);
        logger(get_user_active() . ' - report: ' . json_encode($report));

        $report['filename']= $_FILES['clients_file']['name'];
        $_SESSION['report'] = $report;

        $this->upload_file_form();
    }

    public function export_clients_xlsx(){


    if (!check_session() || $_SESSION['user']->profile != 'agent') {
            header('Location:index.php');
            exit;
        }


        $model = new Agents();
        $results = $model->get_agent_clients($_SESSION['user']->id);

        $data = [ 'name','gender','birthdate','email','phone','interests','created_at','updated_at'];

        foreach ($results['data'] as $client) {
            unset($client->id);
            $data[]=(array)$client;
        }

        $filename = 'output_'.time(). '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet,'dados');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.urlencode($filename).'"');
        $writer->save('php://output');

        logger(get_user_active(). '- fez download da lista de clientes para o arquivo'.$filename. "| total: ". count($data) - 1 . "registros.");


    }
}
