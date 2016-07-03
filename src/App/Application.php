<?php

namespace App;

use Silex\Application as SilexApplication;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationList;

class Application extends SilexApplication
{
    private $environment;

    public function __construct($environment)
    {
        $this->environment = $environment;

        parent::__construct();

        $this->loadConfigurations();

        if ($this['debug']) {
            Debug::enable();
        }

        $this->register(new ServiceProvider());

        $this->mount('', new ControllerProvider());
    }

    /**
     * Get the application root
     */
    public function getRootDir()
    {
        return __DIR__ . '/../../';
    }

    /**
     * Get current environment
     */
    public function getEnvironment()
    {
        return $this->environment ?: 'dev';
    }

    /**
     * Response an error
     * @param string|array|ConstraintViolationList $message The error message
     * @param integer $code The http status code
     * @param array $headers
     * @return JsonResponse
     */
    public function responseError($message, $code = 500, $headers = [])
    {
        if ($message instanceof ConstraintViolationList) {
            $tmp = [];
            foreach ($message as $message) {
                $tmp[] = $message->getPropertyPath() . ': ' . $message->getMessage();
            }
            $message = $tmp;
        }

        return new JsonResponse([
            'messages' => is_array($message) ? $message : [$message],
            'error' => true
        ], $code, $headers);
    }

    /**
     * Load the Configurations
     */
    private function loadConfigurations()
    {
        $file = strtr('{root}/config.{env}.php', ['{root}' => $this->getRootDir(), '{env}' => $this->getEnvironment()]);
        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $file));
        }

        $configs = require_once $file;
        foreach ($configs as $key => $value) {
            $this[$key] = $value;
        }
    }
}
