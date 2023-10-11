<?php

declare(strict_types=1);

namespace Terpz710\EnderKits;

use pocketmine\plugin\PluginBase;
use Terpz710\EnderKits\Command\KitCommand;
use Terpz710\EnderKits\Command\KitsCommand;
use Terpz710\EnderKits\Command\VIPCommand;
use Terpz710\EnderKits\Task\CoolDownTask;
use Terpz710\EnderKits\Task\CooldownManager;

class Main extends PluginBase {

    public function onEnable(): void {
        $cooldownManager = new CooldownManager($this);
        $this->getServer()->getCommandMap()->register("kit", new KitCommand($this, $cooldownManager));
        $this->getServer()->getCommandMap()->register("kits", new KitsCommand($this, $cooldownManager));
        $this->getServer()->getCommandMap()->register("terpz710vip", new VIPCommand($this));
        $this->saveResource("kits.yml");

        $cooldownTask = new CoolDownTask($this);
        $this->getScheduler()->scheduleRepeatingTask($cooldownTask, 20 * 60);
    }
}
