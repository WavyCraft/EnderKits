<?php

declare(strict_types=1);

namespace Terpz710\EnderKits\Task;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;

class CooldownManager {

    private $cooldownData;

    public function __construct(Plugin $plugin) {
        $this->cooldownData = new Config($plugin->getDataFolder() . "cooldown.yml", Config::YAML);
    }

    public function addKitUsage(Player $player, string $kitName, int $cooldown) {
        $playerName = $player->getName();
        $timestamp = time() + $cooldown; // Calculate the expiration time
        $this->cooldownData->setNested("players.$playerName.$kitName", $timestamp);
        $this->cooldownData->save();
    }

    public function getCooldownTimeLeft(Player $player, string $kitName) {
        $lastUseTime = $this->getLastUseTime($player, $kitName);
        $currentTimestamp = time();
        return max(0, $lastUseTime - $currentTimestamp); // Ensure the time left is not negative
    }

    public function getLastUseTime(Player $player, string $kitName) {
        $playerName = $player->getName();
        return $this->cooldownData->getNested("players.$playerName.$kitName", 0);
    }

    public function hasCooldown(Player $player, string $kitName) {
        $lastUseTime = $this->getLastUseTime($player, $kitName);
        $currentTimestamp = time();

        return $lastUseTime > $currentTimestamp;
    }

    public function clearCooldown(Player $player, string $kitName) {
        $playerName = $player->getName();
        $this->cooldownData->removeNested("players.$playerName.$kitName");
        $this->cooldownData->save();
    }

    public function getKitsUsed(Player $player) {
        $playerName = $player->getName();
        return array_keys($this->cooldownData->getNested("players.$playerName", []));
    }
}
