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

        $kitManager = KitManager::getInstance();
        $kits = $kitManager->getKits();

        if (EnderKits::getInstance()->isUiEnabled()) {
            KitForm::getInstance()->openKitMenu($sender);
            return;
        }

        if (!isset($args["kit"])) {
            $kitNames = [];
            foreach ($kits as $kitKey => $kitData) {
                $kitNames[] = $kitManager->getKitName($kitKey) ?? $kitKey;
            }
            $sender->sendMessage("Available kits: " . (empty($kitNames) ? "No kits available" : implode(", ", $kitNames)));
            return;
        }

        $kitKey = null;
        foreach ($kits as $key => $kitData) {
            if (strcasecmp($args["kit"], $kitManager->getKitName($key) ?? $key) === 0) {
                $kitKey = $key;
                break;
            }
        }

        if ($kitKey === null) {
            $sender->sendMessage("Kit '{$args["kit"]}' does not exist!");
            return;
        }

        $kitManager->giveKit($sender, $kitKey);
    }
}
