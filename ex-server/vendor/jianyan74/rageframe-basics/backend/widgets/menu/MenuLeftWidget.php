<?php
namespace jianyan\basics\backend\widgets\menu;

use yii;
use yii\base\Widget;
use common\enums\StatusEnum;
use jianyan\basics\backend\modules\sys\models\Menu;

/**
 * Class MainLeftWidget
 * @package backend\widgets\left
 */
class MenuLeftWidget extends Widget
{
    public function run()
    {
        return $this->render('menu-left', [
            'models'=> Menu::getMenus(Menu::TYPE_MENU,StatusEnum::ENABLED),
        ]);
    }
}