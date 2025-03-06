<?php

declare(strict_types=1);

namespace terpz710\enderkits\command;

use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use terpz710\enderkits\api\KitManager;

use terpz710\enderkits\form\KitForm;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\args\RawStringArgument;

class KitCommand extends BaseCommand {

    protected function prepare() : void{
        $this->setPermission("enderkits.cmd");

        $this->registerArgument(0, new RawStringArgument("kit"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
        if (!$sender instanceof Player) {
            $sender->sendMessage("Use this command in-game!");
            return;
        }

        $kitManager = KitManager::getInstance();
        $kits = $kitManager->getKits();

        if (EnderKits::getInstance()->isUiEnabled()) {
            KitForm::getInstance()->openKitMenu($sender);
            return;
        }

        if (empty($args["kit"])) {
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