<?php

namespace Terpz710\EnderKits\Task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;

class CoolDownTask extends Task {

    private $plugin;

    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $cooldownManager = new CooldownManager($this->plugin);

        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $kitsUsed = $cooldownManager->getKitsUsed($player);

            foreach ($kitsUsed as $kitName) {
                if ($cooldownManager->hasCooldown($player, $kitName)) {
                    $cooldownManager->clearCooldown($player, $kitName);
                }
            }
        }
    }
}
