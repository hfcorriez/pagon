<?php

namespace Command\DB;

use Pagon\Console;
use Pagon\Route\Command as Route;
use PDO;

class Init extends Route
{
    protected $arguments = array(
        '-f|--force' => array('help' => 'Force create database', 'type' => 'bool'),
        '-d|--data'  => array('help' => 'Insert test data', 'type' => 'bool')
    );

    public function run()
    {
        $config = $this->app->database;
        $dsn = sprintf('%s:host=%s;port=%s', $config['type'], $config['host'], $config['port']);
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        try {
            if ($this->params['force']) {
                // Force create database
                $pdo->exec('DROP DATABASE `' . $config['dbname'] . '`');
                $pdo->exec('CREATE DATABASE `' . $config['dbname'] . '` DEFAULT CHARACTER SET `utf8mb4`;');
            } else {
                // Try to create database
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['dbname'] . '` DEFAULT CHARACTER SET `utf8mb4`;');
            }
            $pdo->exec("USE `{$config['dbname']}`");
            $sql = file_get_contents(APP_DIR . '/migrations/schema.sql');
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            $pdo->exec($sql);
            if ($this->params['data']) {
                $sql = file_get_contents(APP_DIR . '/migrations/data.sql');
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
                $pdo->exec($sql);
            }
        } catch (\PDOException $e) {
            print Console::text('Create database error: ' . $e->getMessage() . PHP_EOL, 'red');
            $this->output->end();
        }
        print Console::text('Init database ok' . PHP_EOL, 'green');
    }
}