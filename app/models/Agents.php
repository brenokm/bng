<?php

namespace bng\Models;

use bng\Models\BaseModel;



class Agents extends BaseModel
{
    public function check_login($username, $password)
    {

        $parametros = [
            ':username' => $username
        ];

        $this->db_connect();
        $results = $this->query("SELECT id,  passwrd FROM agents WHERE name = AES_ENCRYPT(:username, '" . MYSQL_KEY . "') AND deleted_at IS NULL", $parametros);

        if ($results->affected_rows == 0) {
            return ['status' => false];
        }

        if (!password_verify($password, $results->results[0]->passwrd)) {
            return ['status' => false];
        }

        return ['status' => true];
    }

    public function get_user_data($username)
    {
        $parametros = [
            ':username' => $username
        ];
        $this->db_connect();
        $results = $this->query("SELECT id, AES_DECRYPT(name,'" . MYSQL_KEY . "') name, profile FROM agents WHERE AES_ENCRYPT(:username,'" . MYSQL_KEY . "') = name", $parametros);
        return ['status' => 'sucess', 'data' => $results->results[0]];
    }

    public function set_user_last_login($id)
    {
        $parametro = [
            ':id' => $id
        ];
        $this->db_connect();
        $results = $this->non_query("UPDATE agents SET last_login = NOW() WHERE id = :id", $parametro);
        return $results;
    }

    public function get_agent_clients($id_agent)
    {

        $params = [
            ':id_agent' => $id_agent
        ];

        $this->db_connect();

        $results = $this->query(
            "SELECT id, AES_DECRYPT(name, '" . MYSQL_KEY . "') name, gender, birthdate, AES_DECRYPT(email, '" . MYSQL_KEY . "') email, AES_DECRYPT(phone, '" . MYSQL_KEY . "') phone, interests, created_at, updated_at FROM persons WHERE id_agent = :id_agent AND deleted_at IS NULL",
            $params
        );
        //testar no banco de dados com esse comando adaptado
        //SELECT id, CAST(AES_DECRYPT(NAME, 'Vduu47qL51hLn6bkYkY6NlO1nivsmdfD')AS CHAR) name, gender, birthdate, CAST(AES_DECRYPT(email, 'Vduu47qL51hLn6bkYkY6NlO1nivsmdfD')AS CHAR) email, CAST(AES_DECRYPT(phone,'Vduu47qL51hLn6bkYkY6NlO1nivsmdfD')AS CHAR) phone, interests, created_at, updated_at FROM persons WHERE id_agent = 2 AND deleted_at IS NULL  


        return [
            'status' => 'success',
            'data' => $results->results
        ];
    }

    public function check_if_client_exist($data)
    {
        $parametros = [
            ':id_agent' => $_SESSION['user']->id,
            ':name_client' => $data['text_name']
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM persons WHERE AES_ENCRYPT(:name_client,'" . MYSQL_KEY . "') = name AND id_agent = :id_agent",
            $parametros
            //SELECT id,CAST(AES_DECRYPT(NAME, 'Vduu47qL51hLn6bkYkY6NlO1nivsmdfD')AS CHAR)  FROM persons WHERE AES_ENCRYPT("Carlos Santos","Vduu47qL51hLn6bkYkY6NlO1nivsmdfD") = name AND id_agent = 2    
        );
        if ($results->affected_rows == 0) {
            return ['status' => false];
        } else {
            return ['status' => true];
        }
    }

    public function add_new_client($data)
    {


        $birthdate = new \DateTime($data['text_birthdate']);


        $parametros = [
            ':id_agent' => $_SESSION['user']->id,
            ':nome' => $data['text_name'],
            ':gender' => $data['radio_gender'],
            ':bithdate' => $birthdate->format('Y-m-d'),
            ':email' => $data['text_email'],
            ':phone' => $data['text_phone'],
            ':interests' => $data['text_interests']
        ];


        $this->db_connect();
        $this->non_query(
            "INSERT INTO persons VALUES(0,AES_ENCRYPT(:nome, '" . MYSQL_KEY . "'),:gender,:bithdate,AES_ENCRYPT(:email, '" . MYSQL_KEY . "'),AES_ENCRYPT(:phone, '" . MYSQL_KEY . "'),:interests, :id_agent, NOW(), NOW(),NULL);",
            $parametros
        );
    }

    public function  get_client_data($id)
    {
        $parametro = [
            ':id_client' => $id
        ];


        $this->db_connect();
        $results = $this->query(
            "SELECT id, AES_DECRYPT(name, '" . MYSQL_KEY . "') AS name, gender ,birthdate ,AES_DECRYPT(email, '" . MYSQL_KEY . "') AS email ,AES_DECRYPT(phone, '" . MYSQL_KEY . "') AS phone,interests FROM persons WHERE id = :id_client",
            $parametro
        );

        if ($results->affected_rows == 0) {
            return ['status' => 'error'];
        }
        return [
            'status' => 'succes',
            'data' => $results->results[0]
        ];
    }

    public function delete_client($id)
    {
        $parametro = [
            ':id_client' => $id
        ];

        $this->db_connect();
        $this->non_query('DELETE FROM persons WHERE id = :id_client', $parametro);
    }

    public function check_other_client_with_same_name($id_client, $name_client)
    {

        $parametros = [
            ':id_agent' => $_SESSION['user']->id,
            ':id_client' => $id_client,
            ':name_client' => $name_client
        ];

        $this->db_connect();
        $results = $this->query("SELECT id FROM persons WHERE id <> :id_client AND :id_agent AND :id_agent = id_agent AND AES_ENCRYPT(:name_client, '" . MYSQL_KEY . "') = name", $parametros);

        if ($results->affected_rows != 0) {
            return ['status' => true];
        } else {
            return ['status' => false];
        }
    }

    public function update_client_data($id, $data)
    {
        $birthdate = new \DateTime($data['text_birthdate']);
        $params = [
            ':id' => $id,
            ':name' => $data['text_name'],
            ':gender' => $data['radio_gender'],
            ':birthdate' => $birthdate->format('Y-m-d H:i:s'),
            ':email' => $data['text_email'],
            ':phone' => $data['text_phone'],
            ':interests' => $data['text_interests'],
        ];

        $this->db_connect();

        $this->non_query(
            "UPDATE persons SET " .
                "name = AES_ENCRYPT(:name, '" . MYSQL_KEY . "'), " .
                "gender = :gender, " .
                "birthdate = :birthdate, " .
                "email = AES_ENCRYPT(:email, '" . MYSQL_KEY . "'), " .
                "phone = AES_ENCRYPT(:phone, '" . MYSQL_KEY . "'), " .
                "interests = :interests, " .
                "updated_at = NOW() " .
                "WHERE id = :id",
            $params
        );
    }


    public function check_current_password($passAct)
    {
        $parametros = [
            ':id_user' => $_SESSION['user']->id
        ];
        $this->db_connect();
        $results = $this->query("SELECT passwrd FROM agents WHERE id = :id_user", $parametros);

        if (password_verify($passAct, $results->results[0]->passwrd)) {
            return [
                'status' => true
            ];
        } else {
            return [
                'status' => false
            ];
        }
    }


    public function update_agent_password($pass)
    {
        $parametros = [
            ':password' => password_hash($pass, PASSWORD_DEFAULT),
            ':id' => $_SESSION['user']->id
        ];

        $this->db_connect();
        $this->query("UPDATE agents SET passwrd =:password ,updated_at = NOW(), WHERE id=:id", $parametros);
    }

    public function check_new_agent_purl($purl)
    {


        // check if thereis a new agent with this purl
        $params = [
            ':purl' => $purl
        ];

        $this->db_connect();
        $results = $this->query("SELECT id FROM agents WHERE purl = :purl", $params);


        if ($results->affected_rows == 0) {
            return [
                'status' => false
            ];
        } else {
            return [
                'status' => true,
                'id' => $results->results[0]->id
            ];
        }
    }

    public function set_agent_password($id, $password)
    {

        $parametros = [
            ':passwrd' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $id
        ];

        $this->db_connect();
        $this->non_query("UPDATE agents SET passwrd = :passwrd, purl = NULL, updated_at = NOW() WHERE id = :id", $parametros);
    }

    public function set_code_for_recover_password($username)
    {
        $params = [
            ':username' => $username
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM agents " .
                "WHERE AES_ENCRYPT(:username, '" . MYSQL_KEY . "') = name " .
                "AND passwrd IS NOT NULL " .
                "AND deleted_at IS NULL",
            $params
        );

        if ($results->affected_rows == 0) {
            return [
                'status' => 'error'
            ];
        }

        $code = rand(100000, 999999);
        $id = $results->results[0]->id;
        $params = [
            ':id' => $id,
            ':code' => $code
        ];

        $results = $this->non_query(
            "UPDATE agents SET " .
                "code = :code " .
                "WHERE id = :id",
            $params
        );

        return [
            'status' => 'success',
            'id' => $id,
            'code' => $code
        ];
    }
    public function check_if_reset_code_is_correct($id, $code)
    {
        $params = [
            ':id' => $id,
            ':code' => $code
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM agents " .
                "WHERE id = :id AND code = :code",
            $params
        );

        if ($results->affected_rows == 0) {
            return [
                'status' => false
            ];
        } else {
            return [
                'status' => true
            ];
        }
    }
    public function change_agent_password($id, $new_passwrd)
    {
        // updates the current user password
        $params = [
            ':id' => $id,
            ':passwrd' => password_hash($new_passwrd, PASSWORD_DEFAULT)
        ];

        $this->db_connect();
        $this->non_query(
            "UPDATE agents SET " .
                "passwrd = :passwrd, " .
                "updated_at = NOW() " .
                "WHERE id = :id",
            $params
        );
    }
}
