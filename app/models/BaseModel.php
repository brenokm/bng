<?php

namespace bng\Models;

use bng\System\Database;

abstract class BaseModel
{
    public $db;

    public function db_connect()
    {
        $this->db = new Database(MYSQL_CONFIG);
    }

    public function query($sql = "", $params = [])
    {
        return $this->db->execute_query($sql, $params);
    }
    public function non_query($sql = "", $params = []){

    return $this->db->execute_query($sql, $params);
    }
    }
