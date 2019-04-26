<?
namespace ITHive\OpenLinesAdditional;

class Main
{
    const MODULE_ID = 'ithive.openlinesadditional';

    /**
     * получение полного пути модуля или относиельного дректории сайта
     * @param bool $notDocumentRoot
     * @return mixed|string
     */
    public static function GetPatch($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace($_SERVER["DOCUMENT_ROOT"],'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    /**
     * проверка ядра битрикс
     * @return bool
     */
    public static function isVersionD7()
    {
        return CheckVersion(SM_VERSION, '14.00.00');
    }
}