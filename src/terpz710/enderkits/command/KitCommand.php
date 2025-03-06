<?php

declare(strict_types=1);

namespace terpz710\enderkits\command;

use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use terpz710\enderkits\EnderKits;

use terpz710\enderkits\api\KitManager;

use terpz710\enderkits\form\KitForm;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\args\RawStringArgument;

class KitCommand extends BaseCommand {

    protected function prepare() : void{
        $this->setPermission("enderkits.cmd");

        $this->registerArgument(0, new RawStringArgument("kit", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("Use this command in-game!");
            return;
        }

        $sender->sendMessage("UI Enabled: " . (EnderKits::getInstance()->isUiEnabled() ? "true" : "false"));

        $kitManager = KitManager::getInstance();
        $kits = $kitManager->getKits();

        if (EnderKits::getInstance()->isUiEnabled()) {
            KitForm::getInstance()->openKitMenu($sender);
            return;
        }

        if (!isset($args["kit"])) {
            $kitNames = implode(", ", array_keys($kits));
            $sender->sendMessage("Available kits: " . ($kitNames ?: "No kits available"));
            return;
        }

        $kitName = $args["kit"];
        if (!isset($kits[$kitName])) {
            $sender->sendMessage("Kit '$kitName' does not exist!");
            return;
        }

        $kitManager->giveKit($sender, $kitName);
    }
}
