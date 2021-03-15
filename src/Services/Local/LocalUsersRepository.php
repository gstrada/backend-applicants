<?php

namespace Osana\Challenge\Services\Local;

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

class LocalUsersRepository implements UsersRepository
{
    public function findByLogin(Login $login, int $limit = 0): Collection
    {
        $usersCsvPath = $_SERVER["DOCUMENT_ROOT"].'/data/users.csv';
        $profilesCsvPath = $_SERVER["DOCUMENT_ROOT"].'/data/profiles.csv';
    
        // Obtengo la colección de usuarios pública
        $usersCsv = array_map('str_getcsv', file($usersCsvPath));
        array_walk($usersCsv, function(&$a) use ($usersCsv) {
            $a = array_combine($usersCsv[0], $a);
        });
        array_shift($usersCsv);
    
        $profilesCsv = array_map('str_getcsv', file($profilesCsvPath));
        array_walk($profilesCsv, function(&$a) use ($profilesCsv) {
            $a = array_combine($profilesCsv[0], $a);
        });
        array_shift($profilesCsv);
    
        foreach($usersCsv as $csvData) {
            foreach($profilesCsv as $profileData){
                $index = array_search($csvData['id'], $profilesCsv,false);
            }
    
            $oUserData = new stdClass();
            $oProfileData = new stdClass();
    
            $oProfileData->company = $profilesCsv[$index]["company"];
            $oProfileData->name =  $profilesCsv[$index]["name"];
            $oProfileData->location = $profilesCsv[$index]["location"];
    
            $oUserData->id = $csvData["id"];
            $oUserData->login = $csvData["login"];
            $oUserData->type = $csvData["type"];
            $oUserData->profile = $oProfileData;
            $arrCsv  = array();
            array_push($arrCsv, $oUserData);
            
        }
        
        $csvCollection = makeCollection($arrCsv);
        
        if($login->getValue() != "") {
            //Filtro la colección para obtener los usuarios coincidentes con el login del parámetro
            $loginsFiltered = $csvCollection->filter(function($user) use ($login){
                return strpos($user, $login->getValue()) == true;
            });
        }else {
            $loginsFiltered = $csvCollection;
        }
    
        if($limit > 0) { //Si es igual a 1 tiene preferencia el origen local
            //Filtro por cantidad de registros /2 para cumplir con la nota 2 del requerimiento
            $usersLogins = $loginsFiltered->slice(0, intval($limit/2) + 1);
        }
        
        foreach ($usersLogins as $oUser){
           
            $oId = new Id($oUser['id']);
            $oLogin = new Login($oUser->login);
            $oType = new Type('local');
            $oName = new Name($oUser->profile->name ? $oUser->profile->name : "");
            $oCompany = new Company($oUser->profile->company ? $oUser->profile->company : "");
            $oLocation = new Location($oUser->profile->location ? $oUser->profile->location : "");
            
            $oProfile = new Profile($oName, $oCompany, $oLocation);
            $oUserNew = new User($oId, $oLogin, $oType, $oProfile);

            $arrUsers = (array)$oUserNew;
            
        }
        
        return makeCollection($arrUsers);
    }

    public function getByLogin(Login $login, int $limit = 0): User
    {
        $colUser = $this->findByLogin($login, $limit);
        
        $oId = new Id($colUser->first()->get('id'));
        $oLogin = $login;
        $oType = new Type(strtolower($colUser->first()->get('type')));
        $oName = new Name($colUser->first()->get('profile')->name);
        $oCompany = new Company($colUser->first()->get('profile')->company);
        $oLocation = new Location($colUser->first()->get('profile')->location);
        
        $oProfile = new Profile($oName, $oCompany, $oLocation);
        return new User($oId, $oLogin, $oType, $oProfile);
    }

    public function add(User $user): void
    {
        // TODO: implement me
    }
}
