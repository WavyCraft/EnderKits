<?php

namespace Terpz710\EnderKits\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use Terpz710\EnderKits\Task\CooldownManager;

class KitsCommand extends Command implements PluginOwned {

    /** @var Plugin */
    private $plugin;
    
    /** @var CooldownManager */
    private $cooldownManager;

    public function __construct(Plugin $plugin, CooldownManager $cooldownManager) {
        parent::__construct("kits", "List of available kits");
        $this->plugin = $plugin;
        $this->cooldownManager = $cooldownManager;
        $this->setPermission("enderkits.kits");
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $kitConfig = $this->loadKitConfig();

            if ($kitConfig === null) {
                $sender->sendMessage(TextFormat::RED . "Kit configuration is missing or invalid. Please follow the kit format and try again");
                return true;
            }

            $kitsList = [];

            foreach ($kitConfig as $kitName => $kitData) {
                $requiredPermission = $kitData['permissions'] ?? null;

                if ($requiredPermission === "ALL" || ($requiredPermission === "VIP" && $sender->hasPermission("enderkits.vip"))) {
                    if (!$this->cooldownManager->hasCooldown($sender, $kitName)) {
                        $kitsList[] = TextFormat::GREEN . $kitName;
                    } else {
                        $kitsList[] = TextFormat::RED . $kitName . " (Cooldown)";
                    }
                } else {
                    $kitsList[] = TextFormat::RED . $kitName;
                }
            }

            $kitsList = implode(", ", $kitsList);
            $sender->sendMessage("Available kits: $kitsList");
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
}
