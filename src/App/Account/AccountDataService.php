<?php

namespace App\Account;

use App\Application;

class AccountDataService
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $db;

    private $password;

    public function __construct(Application $application)
    {
        $this->db = $application['db'];
        $this->password = $application['password'];
    }

    public function get($uid)
    {
        $query = $this->db->createQueryBuilder();
        $query->select('*');
        $query->from('accounts');
        $query->where('uid = :uid');
        $query->setParameter('uid', $uid);

        return $query->execute()->fetch(\PDO::FETCH_OBJ);
    }

    public function getByEmail($email)
    {
        $query = $this->db->createQueryBuilder();
        $query->select('*');
        $query->from('accounts');
        $query->where('email = :email');
        $query->setParameter('email', $email);

        return $query->execute()->fetch(\PDO::FETCH_OBJ);
    }

    public function validateAccountLogin($email, $rawPassword)
    {
        if (!$account = $this->getByEmail($email)) {
            return false;
        }

        $query = $this->db->createQueryBuilder();
        $query->select('uid');
        $query->from('accounts');
        $query->where('email = :email');
        $query->where('password = :password');
        $query->setParameters([
            'email' => $email,
            'password' => $this->password->encodePassword($rawPassword, $account->salt)
        ]);

        return $query->execute()->fetchColumn();
    }

    public function create($values)
    {
        $default = [
            'status' => 1,
            'salt' => $this->generateSaltPassword(),
            'created' => time(),
            'updated' => time()
        ];
        $values = array_merge($default, $values);
        $values['password'] = $this->password->encodePassword($values['password'], $values['salt']);
        $this->db->insert('accounts', $values);

        return $this->db->lastInsertId();
    }

    public function update($uid, $values)
    {
        if (!empty($values['password'])) {
            $values['salt'] = $this->generateSaltPassword();
            $values['password'] = $this->password->encodePassword($values['password'], $values['salt']);
        }
        $values['updated'] = time();
        $this->db->update('accounts', $values, ['uid' => $uid]);
    }

    public function delete($uid)
    {
        $this->db->delete('accounts', ['uid' => $uid]);
    }

    public function generateSaltPassword()
    {
        $salt = '';
        for ($i = 5; $i--; $i >= 0) {
            $salt .= uniqid(mt_rand(), true);
        }
        $salt = base64_encode($salt);

        return substr($salt, 0, 50);
    }
}
