<?php

namespace App\Console;

use App\SchemaDefinition;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Application as ConsoleApplication;
use App\Application as App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends ConsoleApplication
{
    private $app;

    private $schemaDefinition = null;

    public function __construct(App $app)
    {
        parent::__construct();
        $this->app = $app;
        $this->app->boot();
        $this->schemaDefinition = (new SchemaDefinition())->schema();
        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $this->app->getEnvironment()));

        // Command show the schema definition
        $this->register('doctrine:schema:show')
            ->setDescription('Output schema declaration')
            ->setCode([$this, 'schemaShow']);

        // Command install the schema
        $this->register('doctrine:schema:install')
            ->setDescription('Install schema')
            ->setCode([$this, 'schemaInstall']);

        // Command drop the exist database
        $this->register('doctrine:database:drop')
            ->setName('doctrine:database:drop')
            ->setDescription('Drops the configured databases')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setCode([$this, 'databaseDrop']);

        // Command create new database
        $this->register('doctrine:database:create')
            ->setDescription('Creates the configured databases')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command')
            ->setCode([$this, 'databaseCreate']);
    }

    public function schemaShow(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->schemaDefinition->toSql($this->app['db']->getDatabasePlatform()) as $sql) {
            $output->writeln($sql . ';');
        }
    }

    public function schemaInstall(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->schemaDefinition->toSql($this->app['db']->getDatabasePlatform()) as $sql) {
            $this->app['db']->exec($sql . ';');
        }
        $output->writeln('The schema has been installed.');
    }

    public function databaseDrop(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->app['db'];
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
        if (!$name) {
            throw new \InvalidArgumentException("Connection does not contain a 'path' or 'dbname' parameter and cannot be dropped.");
        }
        if ($input->getOption('force')) {
            // Only quote if we don't have a path
            if (!isset($params['path'])) {
                $name = $connection->getDatabasePlatform()->quoteSingleIdentifier($name);
            }
            try {
                $connection->getSchemaManager()->dropDatabase($name);
                $output->writeln(sprintf('<info>Dropped database for connection named <comment>%s</comment></info>', $name));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<error>Could not drop database for connection named <comment>%s</comment></error>', $name));
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

                return 1;
            }
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln(sprintf('<info>Would drop the database named <comment>%s</comment>.</info>', $name));
            $output->writeln('Please run the operation with --force to execute');
            $output->writeln('<error>All data will be lost!</error>');

            return 2;
        }
    }

    public function databaseCreate(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->app['db'];
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        unset($params['dbname']);
        $tmpConnection = DriverManager::getConnection($params);
        // Only quote if we don't have a path
        if (!isset($params['path'])) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }
        $error = false;
        try {
            $tmpConnection->getSchemaManager()->createDatabase($name);
            $output->writeln(sprintf('<info>Created database for connection named <comment>%s</comment></info>', $name));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not create database for connection named <comment>%s</comment></error>', $name));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            $error = true;
        }
        $tmpConnection->close();

        return $error ? 1 : 0;
    }
}
