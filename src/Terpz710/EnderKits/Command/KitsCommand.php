<?php

namespace Terpz710\EnderKits\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use Terpz710\EnderKits\Task\CooldownManager;

class KitsCommand extends Command implements PluginOwned {

    /** @var Plugin */
    private $plugin;
    private $cooldownManager;

    public function __construct(Plugin $plugin, CooldownManager $cooldownManager) {
        parent::__construct("kits", "List of available kits");
        $this->plugin = $plugin;
        $this->cooldownManager = $cooldownManager;
        $this->setPermission("enderkits.command.kits");
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $kitConfig = $this->loadKitConfig();

            if ($kitConfig === null) {
                $sender->sendMessage(TextFormat::RED . "Kit configuration is missing or invalid. Please follow the kit format and try again.");
                return true;
            }

            $claimedKits = [];
            $availableKits = [];
            $lockedKits = [];

            foreach ($kitConfig as $kitName => $kitData) {
                $requiredPermission = "enderkits.kit." . $kitName;

                if ($this->cooldownManager->hasCooldown($sender, $kitName)) {
                    $claimedKits[] = $kitName;
                } elseif ($sender->hasPermission($requiredPermission)) {
                    $availableKits[] = $kitName;
                } else {
                    $lockedKits[] = $kitName;
                }
            }

            $this->updateKitConfig($sender->getName(), $claimedKits, $lockedKits);

            $sender->sendMessage("Available Kits:§b " . implode("§f,§b ", $availableKits));
            $sender->sendMessage("Claimed Kits:§e " . implode("§f,§e ", $claimedKits));
            $sender->sendMessage("Locked Kits:§c " . implode("§f,§c ", $lockedKits));
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }
        return true;
    }

    private function loadKitConfig() {
        $configPath = $this->plugin->getDataFolder() . "kits.yml";
        if (file_exists($configPath)) {
            $config = new Config($configPath, Config::YAML);
            $kitData = $config->get("kits");

            if ($kitData !== null && is_array($kitData)) {
                return $kitData;
            }
        }
        return [];
    }

    private function updateKitConfig(string $playerName, array $claimedKits, array $lockedKits) {
        $configPath = $this->plugin->getDataFolder() . "kits.yml";

        if (file_exists($configPath)) {
            $config = new Config($configPath, Config::YAML);
            $kitData = $config->get("kits");

            if ($kitData !== null && is_array($kitData)) {
                foreach ($claimedKits as $kitName) {
                    if (!isset($kitData[$kitName])) {
                        $kitData[$kitName] = [];
                    }
                    $kitData[$kitName]["status"] = "claimed";
                }

                foreach ($lockedKits as $kitName) {
                    if (!isset($kitData[$kitName])) {
                        $kitData[$kitName] = [];
                    }
                    $kitData[$kitName]["status"] = "locked";
                }

                $config->set("kits", $kitData);
                $config->save();
            }
        }
    }
}
