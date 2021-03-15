<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ShowUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
    }

    public function __invoke(Request $request, Response $response, array $params): Response
    {
        $type = new Type($params['type']);
        $login = new Login($params['login']);
        
        $getUser = $this->localUsersRepository->getByLogin($login, 1);
    
        $user =  makeCollection([
                'id' => $getUser->getId()->getValue(),
                'login' => $getUser->getLogin()->getValue(),
                'type' => $getUser->getType()->getValue(),
                'profile' => [
                    'name' => $getUser->getProfile()->getName()->getValue(),
                    'company' => $getUser->getProfile()->getCompany()->getValue(),
                    'location' => $getUser->getProfile()->getLocation()->getValue(),
                ]
            ]);
        
        $response->getBody()->write($user->toJson());
    
        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }
}
