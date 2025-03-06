<?php

declare(strict_types=1);

namespace terpz710\enderkits\task;

use pocketmine\scheduler\Task;

use terpz710\enderkits\api\CooldownManager;

class CooldownTask extends Task {

    public function onRun() : void{
        CooldownManager::getInstance()->removeExpiredCooldowns();
    }
}