<?php

namespace Osana\Challenge\Services\GitHub;

use Exception;
use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UsersRepository;
use stdClass;
use Tightenco\Collect\Support\Collection;
use GuzzleHttp\Client as httpClient;

class GitHubUsersRepository implements UsersRepository
{
    public function findByLogin(Login $name, int $limit = 0): Collection{

        // Obtengo la colección de usuarios pública
        try {
            $usersCol = $this->sendRequest('users');
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        
        // Armo una colección con los login
        $usersLogin = makeCollection(json_decode($usersCol->getBody()))->map(function($user){
            return $user->login;
        });
    
        if($name->getValue() != "") {
            //Filtro la colección para obtener los usuarios coincidentes con el login del parámetro
            $loginsFiltered = $usersLogin->filter(function($user) use ($name){
                return strpos($user, $name->getValue()) == true;
            });
        }else {
            $loginsFiltered = $usersLogin;
        }
    
        if($limit > 1) { //Si es igual a 1 tiene preferencia el origen local
            //Filtro por cantidad de registros /2 para cumplir con la nota 2 del requerimiento
            $usersLogins = $loginsFiltered->slice(0, intval($limit/2));
        }
        
        //Con los login obtengo la info completa de acuerdo al modelo de datos
        $usersFullData = $this->getUsersInfo($usersLogins);
        
        return $usersFullData;
    }

    public function getByLogin(Login $name, int $limit = 0): User{
    
    }

    public function add(User $user): void
    {
        throw new OperationNotAllowedException();
    }
    
    private function sendRequest(string $path){
        
        $baseUrlGitHub = env('GITHUB_API_URL');
        $githubUser = env('GITHUB_USER');
        $githubToken = env('GITHUB_TOKEN');
    
        $client = new httpClient();
        $url = $baseUrlGitHub.$path;
        try{
            $result = $client->request('GET', $url, [
                'auth' => [$githubUser, $githubToken],
            ]);
        } catch( Exception $e){
            throw new Exception($e->getMessage());
        }
        
        return $result;
        
    }
    
    private function getUsersInfo( Collection $colIds) {
        foreach($colIds as $key=>$value){
            $users = $this->sendRequest('users/'.$value);
            $array[] = $this->setUserObject(json_decode($users->getBody()));
        }
        return(makeCollection($array));
    }
    
    private function setUserObject($oUser) {
    
        $oId = new Id($oUser->id);
        $oLogin = new Login($oUser->login);
        $oType = new Type('github');
        $oName = new Name($oUser->name ? $oUser->name : "");
        $oCompany = new Company($oUser->company ? $oUser->company : "");
        $oLocation = new Location($oUser->location ? $oUser->location : "");
    
        $oProfile = new Profile($oName, $oCompany, $oLocation);
        return new User($oId, $oLogin, $oType, $oProfile);
    }
    
}
