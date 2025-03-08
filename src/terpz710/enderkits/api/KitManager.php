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

use terpz710\enderkits\utils\Utils;

use terpz710\banknotesplus\BankNotesPlus;

use terpz710\messages\Messages;

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

    public function getKitName(string $kitName) : string{
        $kit = $this->getKit($kitName);
        return $kit["kit_name"];
    }

    public function giveKit(Player $player, string $kitName) : void{
        $config = new Config($this->plugin->getDataFolder() . "messages.yml");
        $kit = $this->getKit($kitName);
        if ($kit === null) {
            $player->sendMessage("Kit does not exist!");
            return;
        }

        if (isset($kit["permissions"]) && !$player->hasPermission($kit["permissions"])) {
            $player->sendMessage((string) new Messages($config, "no-kit-permission"));
            return;
        }

        $uuid = $player->getUniqueId()->toString();

        $this->cooldownManager->getCooldown($uuid, $kitName, function(int $cooldownTime) use ($player, $kitName, $kit, $uuid) {
            $timeNow = time();

            if ($cooldownTime > $timeNow) {
                $remaining = $cooldownTime - $timeNow;
                $formattedTime = Utils::formatCooldownTime($remaining);
                $player->sendMessage((string) new Messages($config, "kit-on-cooldown", ["{time}"], [$formattedTime]));
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

                    $inventory->addItem($item);
                }

                if (isset($kit["banknotes"])) {
                    if ($this->bankNotesPlus instanceof BankNotesPlus) {
                        foreach ($kit["banknotes"] as $amount) {
                            $this->bankNotesPlus->convertToBankNote($player, $amount);
                        }
                    }
                }
            }

            $cooldownDuration = $kit["cooldown"];
            $this->cooldownManager->setCooldown($uuid, $kitName, $timeNow + $cooldownDuration);
            $config = new Config($this->plugin->getDataFolder() . "messages.yml");

            $player->sendMessage((string) new Messages($config, "kit-recieved", ["{kit_name}"], [$kit["kit_name"]]));
        });
    }
}
