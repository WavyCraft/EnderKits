<?php

declare(strict_types=1);

namespace terpz710\enderkits\form;

use pocketmine\player\Player;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

use terpz710\enderkits\EnderKits;

use terpz710\enderkits\api\KitManager;

use terpz710\pocketforms\SimpleForm;
use terpz710\pocketforms\ModalForm;

use terpz710\messages\Messages;

final class KitForm {
    use SingletonTrait;

    public function openKitMenu(Player $player) : void{
        $config = new Config(EnderKits::getInstance()->getDataFolder() . "messages.yml");
        $form = new SimpleForm();
        $form->setTitle((string) new Messages($config, "kit-selection-title"));
        $form->setContent((string) new Messages($config, "kit-selection-content"));

        $kitManager = KitManager::getInstance();
        $kitNames = [];

        foreach ($kitManager->getKits() as $kitKey => $kitData) {
            $displayName = $kitManager->getKitName($kitKey) ?? $kitKey;
            $kitNames[] = $kitKey;
            $form->addButton((string) new Messages($config, "kit-selection-button", ["{kit_name}"], [$displayName]));
        }

        $form->setCallback(function (Player $player, $data) use ($kitNames) {
            if ($data !== null && isset($kitNames[$data])) {
                $this->openKitConfirmation($player, $kitNames[$data]);
            }
        });

        $player->sendForm($form);
    }

    public function openKitConfirmation(Player $player, string $kitKey) : void{
        $config = new Config(EnderKits::getInstance()->getDataFolder() . "messages.yml");
        $kitManager = KitManager::getInstance();
        $kitName = $kitManager->getKitName($kitKey) ?? $kitKey;

        $form = new ModalForm();
        $form->setTitle((string) new Messages($config, "kit-confirmation-title"));
        $form->setContent((string) new Messages($config, "kit-confirmation-content", ["{kit_name}"], [$kitName]));
        $form->setButton1((string) new Messages($config, "kit-confirmation-button-yes"));
        $form->setButton2((string) new Messages($config, "kit-confirmation-button-no"));

        $form->setCallback(function (Player $player, bool $data) use ($kitKey) {
            if ($data) {
                KitManager::getInstance()->giveKit($player, $kitKey);
            } else {
                $player->sendMessage((string) new Messages($config, "kit-confirmation-cancelled"));
            }
        });

        $player->sendForm($form);
    }
}
