<?php

declare(strict_types=1);

namespace terpz710\enderkits\form;

use pocketmine\player\Player;

use pocketmine\utils\SingletonTrait;

use terpz710\enderkits\api\KitManager;

use terpz710\pocketforms\SimpleForm;
use terpz710\pocketforms\ModalForm;

final class KitForm {
    use SingletonTrait;

    public function openKitMenu(Player $player) : void{
        $form = new SimpleForm();
        $form->setTitle("Kits");
        $form->setContent("Select a kit:");

        $kitManager = KitManager::getInstance();
        $kitNames = [];

        foreach ($kitManager->getKits() as $kitKey => $kitData) {
            $displayName = $kitManager->getKitName($kitKey) ?? $kitKey;
            $kitNames[] = $kitKey;
            $form->addButton($displayName);
        }

        $form->setCallback(function (Player $player, $data) use ($kitNames) {
            if ($data !== null && isset($kitNames[$data])) {
                $this->openKitConfirmation($player, $kitNames[$data]);
            }
        });

        $player->sendForm($form);
    }

    public function openKitConfirmation(Player $player, string $kitKey) : void{
        $kitManager = KitManager::getInstance();
        $kitName = $kitManager->getKitName($kitKey) ?? $kitKey;

        $form = new ModalForm();
        $form->setTitle("Confirm Kit");
        $form->setContent("Are you sure you want to claim the '$kitName' kit?");
        $form->setButton1("Yes");
        $form->setButton2("No");

        $form->setCallback(function (Player $player, bool $data) use ($kitKey) {
            if ($data) {
                KitManager::getInstance()->giveKit($player, $kitKey);
            } else {
                $player->sendMessage("Kit selection cancelled.");
            }
        });

        $player->sendForm($form);
    }
}
