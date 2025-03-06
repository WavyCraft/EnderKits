<?php

declare(strict_types=1);

namespace terpz710\enderkits\api;

use pocketmine\player\Player;

use pocketmine\item\Item;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TextColor;

use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;

use terpz710\enderkits\EnderKits;

use terpz710\banknotesplus\BankNotesPlus;

final class KitManager {
    use SingletonTrait;

    protected EnderKits $plugin;

    protected BankNotesPlus $bankNotesPlus;

    protected CooldownManager $cooldownManager;

    private Config $kitsConfig;

    public function __construct() {
        $this->plugin = EnderKits::getInstance();
        $this->cooldownManager = CooldownManager::getInstance();
        $this->bankNotesPlus = BankNotesPlus::getInstance();

        $this->kitsConfig = new Config($this->plugin->getDataFolder() . "kits.yml");
    }

    public function getKits() : array{
        return $this->kitsConfig->get("kits", []);
    }

    public function getKit(string $kitName) : ?array{
        return $this->kitsConfig->getNested("kits.$kitName");
    }

    public function giveKit(Player $player, string $kitName) : void{
        $kit = $this->getKit($kitName);
        if ($kit === null) {
            $player->sendMessage(TextColor::RED . "Kit does not exist!");
            return;
        }

        if (isset($kit["permissions"]) && !$player->hasPermission($kit["permissions"])) {
            $player->sendMessage(TextColor::RED . "You don't have permission to use this kit!");
            return;
        }

        $uuid = $player->getUniqueId()->toString();

        $this->cooldownManager->getCooldown($uuid, $kitName, function(int $cooldownTime) use ($player, $kitName, $kit, $uuid) {
            $timeNow = time();

            if ($cooldownTime > $timeNow) {
                $remaining = $cooldownTime - $timeNow;
                $formattedTime = $this->formatCooldownTime($remaining);
                $player->sendMessage(TextColor::RED . "You must wait $formattedTime before using this kit again.");
                return;
            }

            $inventory = $player->getInventory();
            $armorInventory = $player->getArmorInventory();

            if (isset($kit["armor"])) {
                foreach ($kit["armor"] as $slot => $armorData) {
                    if (!isset($armorData["item"])) continue;

                    $item = StringToItemParser::getInstance()->parse($armorData["item"]);
                    if ($item === null) continue;

                    if (isset($armorData["name"])) {
                        $item->setCustomName(TextColor::colorize($armorData["name"]));
                    }

                    if (isset($armorData["enchantments"])) {
                        foreach ($armorData["enchantments"] as $enchantName => $level) {
                            $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantName);
                            if ($enchantment !== null) {
                                $item->addEnchantment(new EnchantmentInstance($enchantment, $level));
                            }
                        }
                }

                    $hasArmorEquipped = match ($slot) {
                        "helmet" => !$armorInventory->getHelmet()->isNull(),
                        "chestplate" => !$armorInventory->getChestplate()->isNull(),
                        "leggings" => !$armorInventory->getLeggings()->isNull(),
                        "boots" => !$armorInventory->getBoots()->isNull(),
                        default => false
                    };

                    if ($hasArmorEquipped) {
                        $inventory->addItem($item);
                    } else {
                        match ($slot) {
                            "helmet" => $armorInventory->setHelmet($item),
                            "chestplate" => $armorInventory->setChestplate($item),
                            "leggings" => $armorInventory->setLeggings($item),
                            "boots" => $armorInventory->setBoots($item),
                            default => null
                        };
                    }
                }
            }

            if (isset($kit["items"]) && is_array($kit["items"])) {
                foreach ($kit["items"] as $itemData) {
                    if (!isset($itemData["item"])) continue;

                    $item = StringToItemParser::getInstance()->parse($itemData["item"]);
                    if ($item === null) continue;

                    if (isset($itemData["quantity"])) {
                        $item->setCount($itemData["quantity"]);
                    }

                    if (isset($itemData["name"])) {
                        $item->setCustomName(TextColor::colorize($itemData["name"]));
                    }

                    if (isset($itemData["enchantments"])) {
                        foreach ($itemData["enchantments"] as $enchantName => $level) {
                            $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantName);
                            if ($enchantment !== null) {
                                $item->addEnchantment(new EnchantmentInstance($enchantment, $level));
                            }
                        }
                    }

                    if (isset($kit["banknotes"]) && is_array($kit["banknotes"])) {
                        foreach ($kit["banknotes"] as $banknoteData) {
                            if (!isset($banknoteData["amount"])) continue;

                            $amount = $banknoteData["amount"];
                            $quantity = $banknoteData["quantity"];

                            $bankNoteItem = $this->bankNotesPlus->getBankNote($amount, $quantity);
                            $player->getInventory()->addItem($bankNoteItem);
                        }
                    }

                    $inventory->addItem($item);
                }
            }

            $cooldownDuration = $kit["cooldown"];
            $this->cooldownManager->setCooldown($uuid, $kitName, $timeNow + $cooldownDuration);

            $player->sendMessage(TextColor::GREEN . "You received the $kitName kit!");
        });
    }
    
    private function formatCooldownTime(int $seconds) : string{
        $timeUnits = [
            "year" => 31536000,
            "month" => 2628002,
            "week" => 604800,
            "day" => 86400,
            "hour" => 3600,
            "minute" => 60,
            "second" => 1
        ];

        $result = [];

        foreach ($timeUnits as $unit => $value) {
            if ($seconds >= $value) {
                $count = intdiv($seconds, $value);
                $seconds %= $value;
                $result[] = "$count $unit" . ($count > 1 ? "s" : "");
            }
        }

        return empty($result) ? "0 seconds" : implode(", ", $result);
    }
}
