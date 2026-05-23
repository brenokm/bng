<?php

namespace bng\Models;

use bng\Models\BaseModel;


class AdminModel extends BaseModel
{

    public function get_all_clients()
    {
        $this->db_connect();
        $results = $this->query("SELECT p.id, " .
            "AES_DECRYPT(p.name,'" . MYSQL_KEY . "') AS name," .
            "p.gender, p.birthdate, " .
            "AES_DECRYPT(p.email,'" . MYSQL_KEY . "') AS email, " .
            "AES_DECRYPT(p.phone,'" . MYSQL_KEY . "') AS phone, " .
            "p.interests, p.created_at, " .
            "AES_DECRYPT(a.name,'" . MYSQL_KEY . "') AS agent " .
            "FROM persons p LEFT JOIN agents a " .
            "ON p.id_agent = a.id " .
            "WHERE p.deleted_at IS NULL " .
            "ORDER BY created_at DESC");

        return $results;
    }

    public function get_agents_clients_stats()
    {

        $this->db_connect();
        $sql =
            "SELECT * FROM (" .
            "SELECT " .
            "p.id_agent, " .
            "AES_DECRYPT(a.name, '" . MYSQL_KEY . "') agente, " .
            "COUNT(*) total_clientes " .
            "FROM persons p " .
            "LEFT JOIN agents a " .
            "ON a.id = p.id_agent " .
            "WHERE p.deleted_at IS NULL " .
            "GROUP BY id_agent ) a " .
            "ORDER BY total_clientes DESC";
        $results = $this->query($sql);
        return $results->results;
    }


    public function get_global_stats()
    {
        $this->db_connect();

        $results['total_agents'] = $this->query("SELECT COUNT(*) AS value FROM agents")->results[0];
        $results['total_clients'] = $this->query("SELECT COUNT(*) AS value FROM persons WHERE deleted_at IS NULL")->results[0];
        $results['total_deleted_clients'] = $this->query("SELECT COUNT(*) AS value FROM persons WHERE deleted_at IS NOT NULL")->results[0];
        $results['average_clients_per_agents'] = $this->query("SELECT (total_clients / total_agents) AS value FROM (SELECT (SELECT COUNT(*) FROM persons WHERE deleted_at IS NULL) AS total_clients, (SELECT COUNT(*) FROM agents) AS total_agents) a")->results[0];
        $results['youngest_client'] = $this->query("SELECT TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS value FROM persons ORDER BY birthdate DESC LIMIT 1")->results[0];
        $results['oldest_client'] = $this->query("SELECT TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS value FROM persons ORDER BY birthdate ASC LIMIT 1")->results[0];
        $results['average_age'] = $this->query("SELECT AVG(TIMESTAMPDIFF(YEAR, birthdate, CURDATE())) AS value FROM persons")->results[0];
        $results['percentage_males'] = $this->query("SELECT CAST((total_males / total_clients) * 100 AS DECIMAL(5,2)) AS value FROM (SELECT (SELECT COUNT(*) FROM persons WHERE deleted_at IS NULL) AS total_clients, (SELECT COUNT(*) FROM persons WHERE gender = 'm') AS total_males) a")->results[0];
        $results['percentage_females'] = $this->query("SELECT CAST((total_females / total_clients) * 100 AS DECIMAL(5,2)) AS value FROM (SELECT (SELECT COUNT(*) FROM persons WHERE deleted_at IS NULL) AS total_clients, (SELECT COUNT(*) FROM persons WHERE gender = 'f') AS total_females) a")->results[0];

        return $results;
    }


    public function get_agents_for_management()
    {

        $this->db_connect();
        $results = $this->query("SELECT id, AES_DECRYPT(name, '" . MYSQL_KEY . "') AS name,passwrd, profile, last_login, created_at, updated_at, deleted_at FROM agents");
        return $results;
    }

    public function check_if_user_exists_with_same_name($name)
    {

        $params = [
            ':name' => $name
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM agents " .
                "WHERE AES_ENCRYPT(:name, '" . MYSQL_KEY . "') = name",
            $params
        );

        if ($results->affected_rows == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function add_new_agent($data)
    {
        // add new agent to the database

        // generate purl
        $chars = 'abcdefghijkabcdefghijkabcdefghijkABCDEFGHIJKABCDEFGHIJKABCDEFGHIJK';
        $purl = substr(str_shuffle($chars), 0, 20);

        $params = [
            ':name' => $data['text_name'],
            ':profile' => $data['select_profile'],
            ':purl' => $purl
        ];

        $this->db_connect();
        $results = $this->non_query(
            "INSERT INTO agents VALUES(" .
                "0, " .
                "AES_ENCRYPT(:name, '" . MYSQL_KEY . "'), " .
                "NULL, " .
                ":profile, " .
                ":purl, " .
                "NULL, " .
                "NULL, " .
                "NOW(), " .
                "NULL, " .
                "NULL)",
            $params
        );

        if ($results->affected_rows == 0) {
            return [
                'status' => 'error'
            ];
        } else {
            return [
                'status' => 'success',
                'email' => $data['text_name'],
                'purl' => $purl
            ];
        }
    }

    public function get_agent_data($id)
    {
        // get agent data to be edited
        $params = [
            ':id' => $id
        ];

        $this->db_connect();
        $results = $this->query(
            "SELECT id, AES_DECRYPT(`name`, '" . MYSQL_KEY . "') `name`, profile, created_at, updated_at, deleted_at FROM agents WHERE id = :id",
            $params
        );
        return $results;
    }

    public function check_if_another_user_exists_with_same_name($id, $name)
    {
        // check if there is another agent with the same name (email)
        $params = [
            ':id' => $id,
            ':name' => $name
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT id FROM agents WHERE AES_ENCRYPT(:name, '" . MYSQL_KEY . "') = name AND id <> :id",
            $params
        );

        return $results->affected_rows != 0 ? true : false;
    }

    public function edit_agent($id, $data)
    {
        // updates the agent's information
        $params = [
            ':id' => $id,
            ':name' => $data['text_name'],
            ':profile' => $data['select_profile']
        ];

        $this->db_connect();
        $results = $this->query(
            "UPDATE agents SET name = AES_ENCRYPT(:name, '" . MYSQL_KEY . "'), profile = :profile, updated_at = NOW() WHERE id = :id",
            $params
        );
        return $results;
    }

    public function get_agent_data_and_total_clients($id)
    {
        // returns the agent personal data and total clients
        $params = [
            ':id' => $id
        ];
        $this->db_connect();
        $results = $this->query(
            "SELECT " .
                "id, " .
                "AES_DECRYPT(`name`, '" . MYSQL_KEY . "') `name`, " .
                "profile, " .
                "created_at, " .
                "updated_at, " .
                "deleted_at, " .
                "(SELECT COUNT(*) FROM persons WHERE id_agent = :id) total_clients " .
                "FROM agents " .
                "WHERE id = :id",
            $params
        );
        return $results;
    }

    // =======================================================
    public function delete_agent($id)
    {
        // soft deletes the agent
        $params = [
            ':id' => $id
        ];
        $this->db_connect();
        $results = $this->non_query(
            "UPDATE agents SET deleted_at = NOW() WHERE id = :id",
            $params
        );
        return $results;
    }

    

    // =======================================================
    public function recover_agent($id)
    {
        // recover the agent
        $params = [
            ':id' => $id
        ];
        $this->db_connect();
        $results = $this->non_query(
            "UPDATE agents SET " .
                "deleted_at = NULL " .
                "WHERE id = :id",
            $params
        );
        return $results;
    }

    // =======================================================
    public function get_agents_data_and_total_clients()
    {
        // returns total information about agents
        $this->db_connect();
        $results = $this->query(
            "SELECT " .
                "AES_DECRYPT(`name`, 'Vduu47qL51hLn6bkYkY6NlO1nivsmdfD') `name`, " .
                "`profile`, " .
                "CASE " .
                "WHEN passwrd IS NOT NULL THEN 'active' " .
                "WHEN passwrd IS NULL THEN 'not active' " .
                "END `active`, " .
                "last_login, " .
                "created_at, " .
                "updated_at, " .
                "deleted_at, " .
                "a.total_active_clients, " .
                "b.total_deleted_clients " .
                "FROM agents LEFT JOIN " .
                "( " .
                "SELECT id_agent, COUNT(*) total_active_clients FROM persons WHERE deleted_at IS NULL " .
                "GROUP BY id_agent " .
                ") a " .
                "ON id = a.id_agent " .
                "LEFT JOIN " .
                "( " .
                "SELECT id_agent, COUNT(*) total_deleted_clients FROM persons WHERE deleted_at IS NOT NULL " .
                "GROUP BY id_agent " .
                ") b " .
                "ON id = b.id_agent"
        );
        return $results;
    }

    
}
