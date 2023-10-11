<?php

namespace Terpz710\EnderKits\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;

class VIPCommand extends Command implements PluginOwned {

    /** @var Plugin */
    private $plugin;

    public function __construct(Plugin $plugin) {
        parent::__construct("terpz710vip", "Get VIP privileges;)", "/terpz710vip");
        $this->plugin = $plugin;
        $this->setPermission("enderkits.vip");
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $sender->sendMessage("This command does nothing;)");
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }
        return true;
    }
}
