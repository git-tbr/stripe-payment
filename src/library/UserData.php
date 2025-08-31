<?php

namespace StripePayment\library;

class UserData
{
    private $userId;

    public function __construct($userId = null)
    {
        if($userId != null){
            $this->userId = $userId;
        }
    }

    public function getUserData(): array | int
    {
        $search = sql([
            "statement" => "SELECT * FROM tbrevent.registration WHERE id = ?",
            "types" => "i",
            "parameters" => [
                $this->userId
            ],
            "only_first_row" => "1"
        ]);
        
        return isset($search) ? $search : 0;
    }

    public function getValueByCat($stc)
    {
        $search = sql([
            "statement" => "SELECT * FROM tbrevent.subscribe_training_center WHERE id = ?",
            "types" => "i",
            "parameters" => [
                $stc
            ],
            "only_first_row" => "1"
        ]);
        
        return isset($search) ? $search['value'] * 100 : 0;
    }

    public function getUserDataByHash($controle): array | int
    {
        $search = sql([
            "statement" => "SELECT * FROM tbrevent.registration WHERE control_hash = ?",
            "types" => "s",
            "parameters" => [
                $controle
            ],
            "only_first_row" => "1"
        ]);
        
        return isset($search) ? $search : 0;
    }

    public function updateUser($estado, $id){
        sql([
            "statement" => "UPDATE tbrevent.registration SET `enable`=? WHERE id=?",
            "types" => "ii",
            "parameters" => [
                $estado,
                $id
            ]
        ]);
    }
}
