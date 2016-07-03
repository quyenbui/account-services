<?php

namespace App\Account;

use App\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class AccountController
{
    private $app;

    /**
     * @var AccountDataService()
     */
    private $dataService;

    /**
     * @var \Symfony\Component\Validator\Validator\RecursiveValidator()
     */
    private $validator;

    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->dataService = $application['data.account'];
        $this->validator = $this->app['validator'];
    }

    public function create(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $constraint = new Assert\Collection([
            'email' => new Assert\Email(),
            'password' => new Assert\NotBlank(),
            'first_name' => new Assert\NotBlank(),
            'last_name' => new Assert\NotBlank(),
            'avatar' => new Assert\NotBlank(),
        ]);
        $errors = $this->validator->validate($data, $constraint);

        if ($errors->count() > 0) {
            return $this->app->responseError($errors, 400);
        }

        if ($this->dataService->getByEmail($data['email'])) {
            return $this->app->responseError(sprintf('The email address %s is taken by another account', $data['email']), 400);
        }

        $uid = $this->dataService->create($data);

        return $this->get($uid);
    }

    public function login(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $constraint = new Assert\Collection([
            'email' => new Assert\NotBlank([
                'message' => 'The email address is required'
            ]),
            'password' => new Assert\NotBlank([
                'message' => 'The password is required'
            ])
        ]);
        $errors = $this->validator->validate($data, $constraint);

        if ($errors->count() > 0) {
            return $this->app->responseError($errors, 400);
        }

        if (!$uid = $this->dataService->validateAccountLogin($data['email'], $data['password'])) {
            return $this->app->responseError('Wrong username or password', 401);
        }

        return $this->get($uid);
    }

    public function patch($uid, Request $request)
    {
        $account = $this->dataService->get($uid);

        if (!$account) {
            return $this->app->responseError(sprintf('The user %s is not exist', $uid), 400);
        }

        $data = json_decode($request->getContent(), true);

        $constraint = new Assert\Collection([
            'password' => new Assert\NotBlank(),
            'first_name' => new Assert\NotBlank(),
            'last_name' => new Assert\NotBlank(),
            'avatar' => new Assert\NotBlank(),
        ], ['allowMissingFields' => true]);
        $errors = $this->validator->validate($data, $constraint);

        if ($errors->count() > 0) {
            return $this->app->responseError($errors, 400);
        }

        $this->dataService->update($uid, $data);

        return $this->get($uid);
    }

    public function get($uid)
    {
        $account = $this->dataService->get($uid);

        if (!$account) {
            return $this->app->responseError(sprintf('The user %s is not exist', $uid), 400);
        }

        unset($account->password, $account->salt);

        return new JsonResponse(['data' => $account]);
    }
}
