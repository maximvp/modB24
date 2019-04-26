<?
/**
 * User: Maksim Pugleev
 * Contact: general@webkd.ru
 * Project vkusvill.ru
 * Date: 30.11.2018
 * Time: 10:58
 */

namespace ITHive\OpenLinesAdditional;
use \Bitrix\Main\Config\Option;

class Vkgroup extends Vkattachment
{
    public static function usersAdmin($idGroup){
        $params = [
            'group_id' =>  abs($idGroup),
            'fields' => 'photo_100',
            'filter' => 'managers',
            'sort' => 'id_asc'
        ];

        $users = parent::_call('groups.getMembers',$params);

        foreach ($users['items'] as $key=>$value){
            if($value['role'] == 'administrator'){
                $usersAdmin[$value['id']] = $value['first_name'].' '.$value['last_name'];
            }
        }
        return $usersAdmin;
    }
}