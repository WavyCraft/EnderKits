<?php

declare(strict_types=1);

namespace Terpz710\EnderKits\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use Terpz710\EnderKits\Task\CooldownManager;
use pocketmine\permission\DefaultPermissions;
use DaPigGuy\PiggyCustomEnchants\enchants\CustomEnchantManager;
use Terpz710\BankNotesPlus\BankNotesPlus;

class KitCommand extends Command implements PluginOwned {

    private $plugin;
    private $cooldownManager;
    private $bankNotesPlusPlugin;

    public function __construct(Plugin $plugin, CooldownManager $cooldownManager, BankNotesPlus $bankNotesPlusPlugin) {
        parent::__construct("kit", "Grab a kit! See the list of kits using /kits", "/kit <kitName>");
        $this->plugin = $plugin;
        $this->cooldownManager = $cooldownManager;
        $this->bankNotesPlusPlugin = $bankNotesPlusPlugin;
        $this->setPermission("enderkits.kit");
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            if (count($args) === 1) {
                $kitName = $args[0];
                $kitConfig = $this->loadKitConfig();

                if ($kitConfig === null) {
                    $sender->sendMessage(TextFormat::RED . "Kit configuration is missing or invalid. Please follow the kit format and try again.");
                    return true;
                }

                if (isset($kitConfig[$kitName])) {
                    $playerPermissions = $sender->getEffectivePermissions();
                    $isOp = false;

                    foreach ($playerPermissions as $permission) {
                        if (strtolower($permission->getPermission()) === DefaultPermissions::ROOT_OPERATOR) {
                            $isOp = true;
                            break;
                        }
                    }

                    $requiredPermission = "enderkits.kit." . $kitName;

                    if (!$isOp) {
                        if (!$this->hasPermission($sender, $requiredPermission)) {
                            $sender->sendMessage(TextFormat::RED . "You don't have the required permission to access the §b{$kitName}§c kit.");
                            return true;
                        }
                    }

                    $cooldownManager = $this->cooldownManager;

                    if (!$cooldownManager->hasCooldown($sender, $kitName)) {
                        $this->applyKit($sender, $kitConfig[$kitName]);

                        $sender->sendMessage(TextFormat::WHITE . "You successfully claimed §b{$kitName}§f!");

                        $cooldown = isset($kitConfig[$kitName]['cooldown']) ? (int) $kitConfig[$kitName]['cooldown'] : 3600;

                        $cooldownManager->addKitUsage($sender, $kitName, $cooldown);
                    } else {
                        $timeLeft = $cooldownManager->getCooldownTimeLeft($sender, $kitName);
                        $sender->sendMessage(TextFormat::WHITE . "You are on cooldown for §b{$kitName}§f. Cooldown remaining: §e{$timeLeft}§f seconds.");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "$kitName §fdoes not exist. Please do §e/kits§f to see the list of kits.");
                }
            } else {
                $sender->sendMessage("Usage: /kit <kitName>");
            }
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }
        return true;
    }

    private function hasPermission(Player $player, string $permission) {
        return $player->hasPermission($permission);
    }

    private function loadKitConfig() {
        $configPath = $this->plugin->getDataFolder() . "kits.yml";
        if (file_exists($configPath)) {
            $config = new Config($configPath, Config::YAML);
            $kitData = $config->get("kits");

            if ($kitData !== null && is_array($kitData)) {
                return $kitData;
            }
        }
        return [];
    }

    private function applyKit(Player $player, array $kitData) {
        $extraArmor = [];

        if (isset($kitData["armor"])) {
            $armorInventory = $player->getArmorInventory();
            foreach (["helmet", "chestplate", "leggings", "boots"] as $armorType) {
                if (isset($kitData["armor"][$armorType])) {
                    $armorData = $kitData["armor"][$armorType];
                    $itemString = $armorData["item"];
                    $item = StringToItemParser::getInstance()->parse($itemString);

                    if ($item !== null) {
                        if (isset($armorData["enchantments"])) {
                            foreach ($armorData["enchantments"] as $enchantmentName => $level) {
                                $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);
                                if ($enchantment === null && class_exists(CustomEnchantManager::class)) {
                                    $enchantment = CustomEnchantManager::getEnchantmentByName($enchantmentName);
                                }
                                if ($enchantment !== null) {
                                    $enchantmentInstance = new EnchantmentInstance($enchantment, (int) $level);
                                    $item->addEnchantment($enchantmentInstance);
                                }
                            }
                        }

                        $currentArmorItem = $armorInventory->{"get" . ucfirst($armorType)}();
                        if ($currentArmorItem->isNull()) {
                            $armorInventory->{"set" . ucfirst($armorType)}($item);
                        } else {
                            $extraArmor[] = $item;
                        }

                        if (isset($armorData["name"])) {
                            $item->setCustomName(TextFormat::colorize($armorData["name"]));
                        }
                    }
                }
            }

            // Filter out null values from $extraArmor array
            $extraArmor = array_filter($extraArmor, function ($item) {
                return $item !== null;
            });

            $player->getInventory()->addItem(...$extraArmor);
        }

        if (isset($kitData["items"])) {
            $items = [];
            $inventory = $player->getInventory();

            foreach ($kitData["items"] as $itemName => $itemData) {
                $item = StringToItemParser::getInstance()->parse($itemName);

                if (isset($itemData["enchantments"])) {
                    foreach ($itemData["enchantments"] as $enchantmentName => $level) {
                        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);
                        if ($enchantment === null && class_exists(CustomEnchantManager::class)) {
                            $enchantment = CustomEnchantManager::getEnchantmentByName($enchantmentName);
                        }
                        if ($enchantment !== null) {
                            $enchantmentInstance = new EnchantmentInstance($enchantment, (int) $level);
                            $item->addEnchantment($enchantmentInstance);
                        }
                    }
                }

                if ($item !== null) {
                    $item->setCount((int) $itemData["quantity"]);
                }
                if (isset($itemData["name"])) {
                    $item->setCustomName(TextFormat::colorize($itemData["name"]));
                }

                $items[] = $item;
            }

            $inventory->addItem(...$items);
        }

        if (isset($kitData["banknotes"])) {
            if ($this->bankNotesPlusPlugin instanceof BankNotesPlus) {
                foreach ($kitData["banknotes"] as $amount) {
                    $this->bankNotesPlusPlugin->convertToBankNote($player, $amount);
                }
            }
        }
    }
}
