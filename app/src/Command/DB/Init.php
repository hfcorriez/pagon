<?php

namespace Command\DB;

use Pagon\Route\Cli as Route;
use Pagon\Cli;
use PDO;

class Init extends Route
{
    protected $arguments = array(
        '-f|--force' => array('help' => 'Force create database', 'type' => 'bool'),
        '-d|--data'  => array('help' => 'Insert test data', 'type' => 'bool')
    );

    public function run()
    {
        $pdo = $this->app->pdo;
        $config = $this->app->get("database");

        try {
            if ($this->params['force']) {
                // Force create database
                $pdo->exec('DROP DATABASE `' . $config['dbname'] . '`');
                $pdo->exec('CREATE DATABASE `' . $config['dbname'] . '` DEFAULT CHARACTER SET `utf8`;');
            } else {
                // Try to create database
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['dbname'] . '` DEFAULT CHARACTER SET `utf8`;');
            }

            $pdo->exec("USE `{$config['dbname']}`");
            $sql = file_get_contents(APP_DIR . '/migrations/schema.sql');
            if (!$sql) {
                print Cli::text('migrations/schema.sql is empty' . PHP_EOL, 'red');
            } else {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
                $pdo->exec($sql);
            }

            if ($this->params['data'] && is_file(APP_DIR . '/migrations/data.sql')) {
                $sql = file_get_contents(APP_DIR . '/migrations/data.sql');
                if (!$sql) {
                    print Cli::text('migrations/data.sql is empty' . PHP_EOL, 'red');
                } else {
                    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
                    $pdo->exec($sql);
                }
            }

        } catch (\PDOException $e) {
            print Cli::text('Create database error: ' . $e->getMessage() . PHP_EOL, 'red');
            $this->output->end();
        }

        print Cli::text('Init database ok' . PHP_EOL, 'green');
    }
}