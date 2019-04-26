<?
namespace ITHive\OpenLinesAdditional;
use \Bitrix\Main\Entity,
    \Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);
/**
 * Class OlTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int optional
 * <li> POST_ID int optional
 * <li> COMMENT_ID int optional
 * <li> MESSAGE string optional
 * <li> CHAT_ID int optional
 * <li> SYSTEM_TYPE string(20) optional
 * <li> CREATED datetime optional
 * </ul>
 **/

class OlTable extends Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'ithive_ol';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('OL_ENTITY_ID_FIELD'),
            ),
            'USER_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('OL_ENTITY_USER_ID_FIELD'),
            ),
            'POST_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('OL_ENTITY_POST_ID_FIELD'),
            ),
            'COMMENT_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('OL_ENTITY_COMMENT_ID_FIELD'),
            ),
            'CHAT_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('OL_ENTITY_CHAT_ID_FIELD'),
            ),
            'OL_CHAT_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage('OL_ENTITY_CHAT_ID_OL_FIELD'),
            ),
            'SYSTEM_TYPE' => array(
                'data_type' => 'string',
                'validation' => array(__CLASS__, 'validateSystemType'),
                'title' => Loc::getMessage('OL_ENTITY_SYSTEM_TYPE_FIELD'),
            ),
            'CREATED' => array(
                'data_type' => 'datetime',
                'title' => Loc::getMessage('OL_ENTITY_CREATED_FIELD'),
            ),
        );
    }
    /**
     * Returns validators for SYSTEM_TYPE field.
     *
     * @return array
     */
    public static function validateSystemType()
    {
        return array(
            new Entity\Validator\Length(null, 20),
        );
    }
}