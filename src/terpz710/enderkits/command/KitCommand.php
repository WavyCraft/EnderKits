<?php

declare(strict_types=1);

namespace terpz710\enderkits\command;

use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use pocketmine\utils\Config;

use terpz710\enderkits\EnderKits;

use terpz710\enderkits\api\KitManager;

use terpz710\enderkits\form\KitForm;

use terpz710\messages\Messages;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\args\RawStringArgument;

class KitCommand extends BaseCommand {

    protected function prepare() : void{
        $this->setPermission("enderkits.cmd");

        $this->registerArgument(0, new RawStringArgument("kit", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
        $config = new Config(EnderKits::getInstance()->getDataFolder() . "messages.yml");
        
        if (!$sender instanceof Player) {
            $sender->sendMessage((string) new Messages($config, "use-command-ingame"));
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
            $sender->sendMessage((string) new Messages($config, "kit-not-found", ["{kit_name}"], [$args["kit"]]));
            return;
        }

        $kitManager->giveKit($sender, $kitKey);
    }
}
