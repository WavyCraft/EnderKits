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

final class KitManager {
    use SingletonTrait;

    protected EnderKits $plugin;

    protected CooldownManager $cooldownManager;

    private Config $kitsConfig;

    public function __construct() {
        $this->plugin = EnderKits::getInstance();
        $this->cooldownManager = CooldownManager::getInstance();

        $this->kitsConfig = new Config($this->plugin->getDataFolder() . "kits.yml");
    }

    public function getKits(): array {
        return $this->kitsConfig->get("kits", []);
    }

    public function getKit(string $kitName): ?array{
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
                $player->sendMessage(TextColor::RED . "You must wait $remaining seconds before using this kit again.");
                return;
            }

            if (isset($kit["armor"])) {
                $armor = $player->getArmorInventory();
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

                    match ($slot) {
                        "helmet" => $armor->setHelmet($item),
                        "chestplate" => $armor->setChestplate($item),
                        "leggings" => $armor->setLeggings($item),
                        "boots" => $armor->setBoots($item),
                        default => null
                    };
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

                    $player->getInventory()->addItem($item);
                }
            }

            $cooldownDuration = $kit["cooldown"];
            $this->cooldownManager->setCooldown($uuid, $kitName, $timeNow + $cooldownDuration);

            $player->sendMessage(TextColor::GREEN . "You received the $kitName kit!");
        });
    }
}
