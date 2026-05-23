<?php

namespace bng\Controllers;

use bng\Models\AdminModel;
use bng\Controllers\BaseController;
use bng\System\SendEmail;

class Admin extends BaseController
{


    public function all_clients()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php?ct=agent&mt=index');
        }
        $model = new AdminModel();
        $results = $model->get_all_clients();
        $data['user'] = $_SESSION['user'];
        $data['clients'] = $results->results;

        //printData($results);
        $this->view('layouts/html_header',);
        $this->view('navbar', $data);
        $this->view('global_clients', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }


    public function export_client_xlsx()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php?ct=agent&mt=index');
        }

        $model = new AdminModel();
        $results = $model->get_all_clients();
        $results = $results->results;

        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'agente', 'created_at'];

        foreach ($results as $client) {
            unset($client->id);
            $data[] = (array)$client;
        }


        $filename = 'output_' . time() . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'dados');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        $writer->save('php://output');

        logger(get_user_active() . '- fez download da lista de clientes para o arquivo' . $filename . "| total: " . count($data) - 1 . "registros.");
    }


    public function stats()
    {




        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php?ct=agent&mt=index');
        }

        $data['user'] = $_SESSION['user'];
        $model = new AdminModel;
        $data['agents'] = $model->get_agents_clients_stats();
        // printData($data['agents']);
        if (count($data['agents']) != 0) {
            $labels_tmp = [];
            $total_tmp = [];
            foreach ($data['agents'] as $agent) {
                $labels_tmp[] = $agent->agente;
                $total_tmp[] = $agent->total_clientes;
            }
            $data['chart_labels'] = '["' . implode('","', $labels_tmp) . '"]';
            $data['chart_totals'] = '["' . implode('","', $total_tmp) . '"]';
            $data['chartjs'] = true;
        }

        $data['global_stats'] = $model->get_global_stats();

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('stats', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    public function create_pdf_report()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
            exit;
        }

        logger(get_user_active() . " - visualizou o PDF");

        $model = new AdminModel();
        $agents = $model->get_agents_clients_stats();
        $global_stats = $model->get_global_stats();

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P'
        ]);

        $x = 50;
        $y = 50;

        $html = "";

        // LOGO
        $html .= '
    <div style="position:absolute; left:' . $x . 'px; top:' . $y . 'px;">
        <img src="assets/images/logo_32.png">
    </div>';

        // TÍTULO
        $html .= '
    <h2 style="position:absolute; left:' . ($x + 50) . 'px; top:' . ($y - 10) . 'px;">
        ' . APP_NAME . '
    </h2>';

        $y += 50;

        // LINHA
        $html .= '
    <div style="
        position:absolute;
        left:' . $x . 'px;
        top:' . $y . 'px;
        width:700px;
        height:1px;
        background-color:rgb(200,200,200);
    "></div>';

        $y += 10;

        // SUBTÍTULO
        $html .= '
    <div style="
        position:absolute;
        left:' . $x . 'px;
        top:' . $y . 'px;
        width:700px;
        text-align:center;
        font-weight:bold;
    ">
        RELATÓRIO DE DADOS DE ' . date('d-m-Y') . '
    </div>';

        $y += 50;

        // TABELA AGENTES
        $html .= '
    <div style="
        position:absolute;
        left:' . ($x + 90) . 'px;
        top:' . $y . 'px;
        width:500px;
    ">
        <table style="
            border:1px solid black;
            border-collapse:collapse;
            width:100%;
        ">
            <thead>
                <tr>
                    <th style="
                        width:60%;
                        border:1px solid black;
                        text-align:left;
                    ">
                        Agente
                    </th>

                    <th style="
                        width:40%;
                        border:1px solid black;
                    ">
                        N.º de Clientes
                    </th>
                </tr>
            </thead>

            <tbody>';

        foreach ($agents as $agent) {

            $html .= '
            <tr>
                <td style="
                    border:1px solid black;
                ">
                    ' . $agent->agente . '
                </td>

                <td style="
                    border:1px solid black;
                    text-align:center;
                ">
                    ' . $agent->total_clientes . '
                </td>
            </tr>';
        }

        $html .= '
            </tbody>
        </table>
    </div>';

        $y += (count($agents) * 25) + 80;

        // TABELA GLOBAL
        $html .= '
    <div style="
        position:absolute;
        left:' . ($x + 90) . 'px;
        top:' . $y . 'px;
        width:500px;
    ">
        <table style="
            border:1px solid black;
            border-collapse:collapse;
            width:100%;
        ">
            <thead>
                <tr>
                    <th style="
                        width:60%;
                        border:1px solid black;
                        text-align:left;
                    ">
                        Item
                    </th>

                    <th style="
                        width:40%;
                        border:1px solid black;
                    ">
                        Valor
                    </th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td style="border:1px solid black;">Total agentes</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['total_agents']->value . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Total clientes</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['total_clients']->value . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Total clientes removidos</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['total_deleted_clients']->value . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Média de clientes por agente</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . sprintf("%.2f", $global_stats['average_clients_per_agents']->value) . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Idade do cliente mais novo</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['youngest_client']->value . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Idade do cliente mais velho</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['oldest_client']->value . '
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Percentagem de homens</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['percentage_males']->value . '%
                    </td>
                </tr>

                <tr>
                    <td style="border:1px solid black;">Percentagem de mulheres</td>
                    <td style="border:1px solid black; text-align:right;">
                        ' . $global_stats['percentage_females']->value . '%
                    </td>
                </tr>

            </tbody>
        </table>
    </div>';

        $pdf->WriteHTML($html);

        $pdf->Output();
    }


    public function agents_management()
    {

        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header("Location: index.php");
        }

        $model = new AdminModel();
        $results = $model->get_agents_for_management();
        //printData($results);
        $data['agents'] = $results->results;
        $data['user'] = $_SESSION['user'];

        $this->view("layouts/html_header", $data);
        $this->view("navbar", $data);
        $this->view("agents_management", $data);
        $this->view("footer");
        $this->view('layouts/html_footer');
    }

    public function new_agent_frm()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
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
        $this->view('agents_add_new_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }
    public function new_agent_submit()
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if there was a post
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: index.php');
        }

        // form validation
        $validation_error = null;

        // check if agent is a valid email
        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validation_error = "O nome do agente deve ser um email válido.";
        }

        // check if profile is valid
        $valid_profiles = ['admin', 'agent'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $valid_profiles)) {
            $validation_error = "O perfil selecionado é inválido.";
        }

        if (!empty($validation_error)) {
            $_SESSION['validation_error'] = $validation_error;
            $this->new_agent_frm();
            return;
        }

        // check if there is already a agent with the same username
        $model = new AdminModel();
        $results = $model->check_if_user_exists_with_same_name($_POST['text_name']);

        if ($results) {

            // there is an agent with that name (email)
            $_SESSION['server_error'] = "Já existe um agente com o mesmo nome.";
            $this->new_agent_frm();
            return;
        }

        // add new agent to the database
        $results = $model->add_new_agent($_POST);

        if ($results['status'] == 'error') {

            // logger
            logger(get_user_active() . " - aconteceu um erro na criação de novo registo de agente.");
            header('Location: index.php');
        }

        // send email with purl
        $url = explode('?', $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        $url = $url[0] . '?ct=main&mt=define_password&purl=' . $results['purl'];
        $email = new SendEmail();
        $data = [
            'to' => $_POST['text_name'],
            'link' => $url
        ];

        $results = $email->send_email(APP_NAME . ' Conclusão do registo de agente', 'email_body_new_agent', $data);
        if ($results['status'] == 'error') {

            // logger
            logger(get_user_active() . " - não foi possível enviar o email para conclusão do registo: " . $_POST['text_name'] . ' - erro: ' . $results['message'], 'error');
            die($results['message']);
        }

        // logger
        logger(get_user_active() . " - enviado com sucesso email para conclusão do registo: " . $_POST['text_name']);

        // display the success page
        $data['user'] = $_SESSION['user'];
        $data['email'] = $_POST['text_name'];

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_email_sent', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function edit_agent($id)
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        if (empty($id)) {
            header('Location: index.php');
        }

        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agents data
        $model = new AdminModel();
        $results = $model->get_agent_data($id);

        // validation error
        if (isset($_SESSION['validation_error'])) {
            $data['validation_error'] = $_SESSION['validation_error'];
            unset($_SESSION['validation_error']);
        }

        // server error
        if (isset($_SESSION['server_error'])) {
            $data['server_error'] = $_SESSION['server_error'];
            unset($_SESSION['server_error']);
        }

        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        // display the edit agent form
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_edit_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function edit_agent_submit()
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if there was a post
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: index.php');
        }

        // check if id is present and valid
        if (empty($_POST['id'])) {
            header('Location: index.php');
        }

        $id = aes_decrypt($_POST['id']);
        if (!$id) {
            header('Location: index.php');
        }

        // form validation
        $validation_error = null;

        // check if agent is a valid email
        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validation_error = "O nome do agente deve ser um email válido.";
        }

        // check if profile is valid
        $valid_profiles = ['admin', 'agent'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $valid_profiles)) {
            $validation_error = "O perfil selecionado é inválido.";
        }

        if (!empty($validation_error)) {
            $_SESSION['validation_error'] = $validation_error;
            $this->edit_agent(aes_encrypt($id));
            return;
        }

        // check if there is already another agent with the same username
        $model = new AdminModel();
        $results = $model->check_if_another_user_exists_with_same_name($id, $_POST['text_name']);

        if ($results) {

            // there is another agent with that name (email)
            $_SESSION['server_error'] = "Já existe outro agente com o mesmo nome.";
            $this->edit_agent(aes_encrypt($id));
            return;
        }

        // edit agent in the database
        $results = $model->edit_agent($id, $_POST);

        if ($results->status == 'error') {

            // logger
            logger(get_user_active() . " - aconteceu um erro na edição de dados do agente ID: $id", 'error');
            header('Location: index.php');
        } else {

            // logger
            logger(get_user_active() . " - editado com sucesso os dados do agente ID: $id - " . $_POST['text_name']);
        }

        // go to the main admin page
        $this->agents_management();
    }

    // =======================================================
    public function edit_delete($id = '')
    {
       
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        $model = new AdminModel();
        $results = $model->get_agent_data_and_total_clients($id);

        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_delete_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function delete_agent_confirm($id = '')
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $idd = aes_decrypt($id);
        if (!$idd) {
            header('Location: index.php');
        }

        // delete agent (soft delete)
        $model = new AdminModel();
        $results = $model->delete_agent($idd);

        if ($results->status == 'success') {

            // logger
            logger(get_user_active() . " - eliminado com sucesso o agente ID: $idd");
        } else {

            // logger
            logger(get_user_active() . " - aconteceu um erro na eliminação do agente ID: $idd   ", 'error');
        }

        // go to the main page
        $this->agents_management();
    }

    // =======================================================
    public function edit_recover($id = '')
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->get_agent_data_and_total_clients($id);

        // display page for confirmation
        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        // display the edit agent form
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_recover_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function recover_agent_confirm($id = '')
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->recover_agent($id);

        if ($results->status == 'success') {

            // logger
            logger(get_user_active() . " - recuperado com sucesso o agente ID: $id");
        } else {

            // logger
            logger(get_user_active() . " - aconteceu um erro na recuperação do agente ID: $id", 'error');
        }

        // go to the main page
        $this->agents_management();
    }


    // =======================================================
    public function export_agents_XLSX()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }
         
        $model = new AdminModel();
        $results = $model->get_agents_data_and_total_clients();
        $results = $results->results;

        $data[] = ['name', 'profile', 'active', 'last login', 'created at', 'updated at', 'deleted at', 'total active clients', 'total deleted clients'];

        foreach ($results as $agent) {

            unset($agent->id);

            $data[] = (array)$agent;
        }

        $filename = 'output_' . time() . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'dados');
        $spreadsheet->addSheet($worksheet);
        $worksheet->fromArray($data);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        $writer->save('php://output');

        // logger
        logger(get_user_active() . " - fez download da lista de agentes para o ficheiro: " . $filename . " | total: " . count($data) - 1 . " registos.");
    }

    
}
