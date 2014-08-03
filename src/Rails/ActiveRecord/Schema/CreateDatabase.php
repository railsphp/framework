<?php
namespace Rails\ActiveRecord\Schema;

class CreateDatabase extends DropDatabase
{
    protected $specifications = array(
        self::DATABASE => 'CREATE DATABASE IF NOT EXISTS %1$s'
    );
}
