<?php

declare(strict_types=1);

namespace terpz710\enderkits\api;

use terpz710\enderkits\EnderKits;

use pocketmine\utils\SingletonTrait;

class CooldownManager {
    use SingletonTrait;

    protected EnderKits $plugin;

    public function __construct() {
        $this->plugin = EnderKits::getInstance();
    }

    public function getCooldown(string $uuid, string $kit, callable $callback) : void{
        $this->plugin->getDataBase()->executeSelect("cooldowns.get", ["uuid" => $uuid, "kit" => $kit], function(array $rows) use ($callback) {
            $cooldownTime = $rows[0]["cooldown"];
            $callback($cooldownTime);
        });
    }

    public function setCooldown(string $uuid, string $kit, int $cooldownTime) : void{
        $this->plugin->getDataBase()->executeChange("cooldowns.set", [
            "uuid" => $uuid,
            "kit" => $kit,
            "cooldown" => $cooldownTime
        ]);
    }

    public function removeExpiredCooldowns() : void{
        $timeNow = time();

        $this->plugin->getDataBase()->executeChange("cooldowns.cleanup", ["time" => $timeNow]);
    }
}
