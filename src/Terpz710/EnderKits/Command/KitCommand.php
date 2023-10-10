<?php

declare(strict_types=1);

namespace Terpz710\EnderKits\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use Terpz710\EnderKits\Main;

class KitCommand extends Command implements PluginOwned {

    /** @var Plugin */
    private $plugin;

    public function __construct(Plugin $plugin) {
        parent::__construct("kit", "Get a kit");
        $this->plugin = $plugin;
        $this->setPermission("enderkits.cmd");
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $helmet = VanillaItems::DIAMOND_HELMET();
            $helmet->setCustomName("Kit Helmet");
            $chestplate = VanillaItems::DIAMOND_CHESTPLATE();
            $chestplate->setCustomName("Kit Chestplate");
            $leggings = VanillaItems::DIAMOND_LEGGINGS();
            $leggings->setCustomName("Kit Leggings");
            $boots = VanillaItems::DIAMOND_BOOTS();
            $boots->setCustomName("Kit Boots");
            $sword = VanillaItems::DIAMOND_SWORD();
            $sword->setCustomName("Kit Sword");

            $sender->getInventory()->addItem($helmet, $chestplate, $leggings, $boots, $sword);
            $sender->sendMessage(TextFormat::GREEN . "You received the Kit!");
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }
        return true;
    }
}
